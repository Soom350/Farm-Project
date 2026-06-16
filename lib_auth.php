<?php
declare(strict_types=1);

require_once __DIR__ . '/lib_db.php';
require_once __DIR__ . '/lib_mail.php';

function auth_user(): ?array
{
    // Mode design only: no DB/auth until the back is connected.
    if (app_frontend_only()) return null;

    $userId = $_SESSION['user_id'] ?? null;
    if (!is_int($userId) && !is_string($userId)) return null;
    $userId = (int)$userId;
    if ($userId <= 0) return null;

    $stmt = db()->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $u = $stmt->fetch();
    return is_array($u) ? $u : null;
}

function auth_is_logged_in(): bool
{
    return auth_user() !== null;
}

function auth_is_email_verified(?array $user = null): bool
{
    $user = $user ?? auth_user();
    if (!$user) return false;
    return !empty($user['email_verified_at']);
}

function auth_require_login(string $next): void
{
    if (app_frontend_only()) return;
    if (auth_is_logged_in()) return;
    redirect(url_with_params('auth/login.php', ['next' => $next]));
}

function auth_require_verified_email(string $next): void
{
    if (app_frontend_only()) return;
    $u = auth_user();
    if (!$u) {
        auth_require_login($next);
        return;
    }
    if (auth_is_email_verified($u)) return;
    redirect(url_with_params('auth/verify-required.php', ['next' => $next]));
}

function auth_password_policy_errors(string $password): array
{
    $errors = [];
    if (mb_strlen($password) < 12) $errors[] = 'Password: 12 characters minimum.';
    if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Password: at least 1 uppercase letter.';
    if (!preg_match('/[a-z]/', $password)) $errors[] = 'Password: at least 1 lowercase letter.';
    if (!preg_match('/[0-9]/', $password)) $errors[] = 'Password: at least 1 number.';
    if (!preg_match('/[^A-Za-z0-9]/', $password)) $errors[] = 'Password: at least 1 special character.';
    return $errors;
}

function auth_normalize_email(string $email): string
{
    return mb_strtolower(trim($email));
}

function auth_rate_limited(string $email, string $ip): bool
{
    if (app_frontend_only()) return false;
    $email = auth_normalize_email($email);
    $ip = trim($ip);
    $since = gmdate('c', time() - 15 * 60);

    $stmt = db()->prepare("
        SELECT COUNT(*) AS c
        FROM login_attempts
        WHERE success = 0
          AND created_at >= :since
          AND (email = :email OR ip = :ip)
    ");
    $stmt->execute([':since' => $since, ':email' => $email, ':ip' => $ip]);
    $row = $stmt->fetch();
    $count = is_array($row) ? (int)($row['c'] ?? 0) : 0;
    return $count >= 8;
}

function auth_log_attempt(?string $email, string $ip, bool $success): void
{
    if (app_frontend_only()) return;
    $stmt = db()->prepare('INSERT INTO login_attempts(email, ip, success, created_at) VALUES(:email, :ip, :success, :created_at)');
    $stmt->execute([
        ':email' => $email ? auth_normalize_email($email) : null,
        ':ip' => $ip,
        ':success' => $success ? 1 : 0,
        ':created_at' => gmdate('c'),
    ]);
}

function auth_create_user(array $data): array
{
    if (app_frontend_only()) {
        return ['ok' => false, 'errors' => ['Mode design: signup disabled (back to do).']];
    }

    $email = auth_normalize_email((string)($data['email'] ?? ''));
    $password = (string)($data['password'] ?? '');
    $fullName = trim((string)($data['full_name'] ?? ''));
    $phone = trim((string)($data['phone'] ?? ''));
    $company = trim((string)($data['company_name'] ?? ''));
    $shipCountry = strtoupper(trim((string)($data['shipping_country'] ?? 'US')));
    $shipLine1 = trim((string)($data['shipping_line1'] ?? ($data['address'] ?? '')));
    $shipCity = trim((string)($data['shipping_city'] ?? ($data['city'] ?? '')));
    $shipRegion = trim((string)($data['shipping_region'] ?? ($data['state'] ?? '')));
    $shipPostal = trim((string)($data['shipping_postal_code'] ?? ($data['zip'] ?? '')));

    $errors = [];
    if ($fullName === '') $errors[] = 'Full name required.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email valide requis.';
    if ($phone === '') $errors[] = 'Phone required.';

    $errors = array_merge($errors, auth_password_policy_errors($password));

    if ($errors) return ['ok' => false, 'errors' => $errors];

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    if ($stmt->fetch()) {
        return ['ok' => false, 'errors' => ['This email is already used.']];
    }

    $now = gmdate('c');
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO users(
            email, password_hash, full_name, phone, company_name, locale, timezone,
            default_shipping_country, default_shipping_line1, default_shipping_city, default_shipping_region, default_shipping_postal_code,
            email_verified_at, created_at, updated_at
        )
        VALUES(
            :email, :ph, :full_name, :phone, :company, :locale, :tz,
            :sc, :sl1, :city, :region, :pc,
            NULL, :created, :updated
        )
    ");
    $stmt->execute([
        ':email' => $email,
        ':ph' => $hash,
        ':full_name' => $fullName,
        ':phone' => $phone !== '' ? $phone : null,
        ':company' => $company !== '' ? $company : null,
        ':locale' => $_SESSION['locale'] ?? 'fr-FR',
        ':tz' => 'UTC',
        ':sc' => $shipCountry !== '' ? $shipCountry : null,
        ':sl1' => $shipLine1 !== '' ? $shipLine1 : null,
        ':city' => $shipCity !== '' ? $shipCity : null,
        ':region' => $shipRegion !== '' ? $shipRegion : null,
        ':pc' => $shipPostal !== '' ? $shipPostal : null,
        ':created' => $now,
        ':updated' => $now,
    ]);

    $userId = (int)$pdo->lastInsertId();
    auth_send_verification_email($userId);

    return ['ok' => true, 'user_id' => $userId];
}

function auth_login(string $email, string $password, string $ip): array
{
    if (app_frontend_only()) {
        return ['ok' => false, 'errors' => ['Mode design: login disabled (back to do).']];
    }

    $email = auth_normalize_email($email);
    $password = (string)$password;

    if (auth_rate_limited($email, $ip)) {
        return ['ok' => false, 'errors' => ['Too many attempts. Try again in a few minutes.']];
    }

    $stmt = db()->prepare('SELECT * FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $u = $stmt->fetch();

    if (!is_array($u) || empty($u['password_hash']) || !password_verify($password, (string)$u['password_hash'])) {
        auth_log_attempt($email, $ip, false);
        // Message volontairement générique (anti-enum)
        return ['ok' => false, 'errors' => ['Email or password incorrect.']];
    }

    auth_log_attempt($email, $ip, true);

    // Anti session fixation
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int)$u['id'];
    $_SESSION['locale'] = (string)($u['locale'] ?? ($_SESSION['locale'] ?? 'en-US'));

    return ['ok' => true, 'user' => $u];
}

function auth_logout(): void
{
    $_SESSION['user_id'] = null;
    unset($_SESSION['user_id']);
    session_regenerate_id(true);
}

function auth_new_token(): array
{
    $raw = bin2hex(random_bytes(32));
    $hash = hash('sha256', $raw);
    return [$raw, $hash];
}

function auth_send_verification_email(int $userId): void
{
    if (app_frontend_only()) return;
    $pdo = db();
    $stmt = $pdo->prepare('SELECT email, full_name FROM users WHERE id = :id LIMIT 1');
    $stmt->execute([':id' => $userId]);
    $u = $stmt->fetch();
    if (!is_array($u)) return;

    [$raw, $hash] = auth_new_token();
    $expires = gmdate('c', time() + 60 * 60 * 24); // 24h

    $pdo->prepare("
        INSERT INTO email_verification_tokens(user_id, token_hash, expires_at, created_at, used_at)
        VALUES(:uid, :th, :exp, :created, NULL)
    ")->execute([
        ':uid' => $userId,
        ':th' => $hash,
        ':exp' => $expires,
        ':created' => gmdate('c'),
    ]);

    $link = url_with_params('auth/verify.php', ['token' => $raw]);
    $body = "Hello,\n\nPlease verify your email by clicking on this link:\n{$link}\n\nThis link expires in 24h.";
    send_email((string)$u['email'], 'Email verification', $body);
}

function auth_verify_email_with_token(string $rawToken): array
{
    if (app_frontend_only()) {
        return ['ok' => false, 'errors' => ['Mode design: email verification disabled (back to do).']];
    }

    $rawToken = trim($rawToken);
    if ($rawToken === '') return ['ok' => false, 'errors' => ['Invalid token.']];

    $hash = hash('sha256', $rawToken);
    $now = gmdate('c');

    $pdo = db();
    $stmt = $pdo->prepare("
        SELECT * FROM email_verification_tokens
        WHERE token_hash = :th
          AND used_at IS NULL
          AND expires_at >= :now
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([':th' => $hash, ':now' => $now]);
    $tok = $stmt->fetch();
    if (!is_array($tok)) {
        return ['ok' => false, 'errors' => ['Verification link invalid or expired.']];
    }

    $userId = (int)$tok['user_id'];
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE email_verification_tokens SET used_at = :now WHERE id = :id')->execute([
            ':now' => $now,
            ':id' => (int)$tok['id'],
        ]);
        $pdo->prepare('UPDATE users SET email_verified_at = :now, updated_at = :now WHERE id = :uid')->execute([
            ':now' => $now,
            ':uid' => $userId,
        ]);
        $pdo->commit();
    } catch (Throwable) {
        $pdo->rollBack();
        return ['ok' => false, 'errors' => ['Error during verification.']];
    }

    // Auto-login si l'utilisateur n'est pas connecté
    if (!auth_is_logged_in()) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $userId;
    }

    return ['ok' => true];
}

function auth_request_password_reset(string $email): void
{
    if (app_frontend_only()) return;
    $email = auth_normalize_email($email);
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) return;

    $pdo = db();
    $stmt = $pdo->prepare('SELECT id, email FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);
    $u = $stmt->fetch();
    if (!is_array($u)) return; // anti-enum: silence

    [$raw, $hash] = auth_new_token();
    $expires = gmdate('c', time() + 60 * 60); // 1h
    $pdo->prepare("
        INSERT INTO password_reset_tokens(user_id, token_hash, expires_at, created_at, used_at)
        VALUES(:uid, :th, :exp, :created, NULL)
    ")->execute([
        ':uid' => (int)$u['id'],
        ':th' => $hash,
        ':exp' => $expires,
        ':created' => gmdate('c'),
    ]);

    $link = url_with_params('auth/reset-password.php', ['token' => $raw]);
    $body = "Hello,\n\nPlease reset your password by clicking on this link:\n{$link}\n\nThis link expires in 1h.";
    send_email((string)$u['email'], 'Réinitialisation du mot de passe', $body);
}

function auth_reset_password(string $rawToken, string $newPassword): array
{
    if (app_frontend_only()) {
        return ['ok' => false, 'errors' => ['Mode design: password reset disabled (back to do).']];
    }

    $rawToken = trim($rawToken);
    if ($rawToken === '') return ['ok' => false, 'errors' => ['Invalid token.']];

    $errors = auth_password_policy_errors($newPassword);
    if ($errors) return ['ok' => false, 'errors' => $errors];

    $hashToken = hash('sha256', $rawToken);
    $now = gmdate('c');
    $pdo = db();

    $stmt = $pdo->prepare("
        SELECT * FROM password_reset_tokens
        WHERE token_hash = :th
          AND used_at IS NULL
          AND expires_at >= :now
        ORDER BY id DESC
        LIMIT 1
    ");
    $stmt->execute([':th' => $hashToken, ':now' => $now]);
    $tok = $stmt->fetch();
    if (!is_array($tok)) return ['ok' => false, 'errors' => ['Invalid link or expired.']];

    $userId = (int)$tok['user_id'];
    $ph = password_hash($newPassword, PASSWORD_DEFAULT);

    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE password_reset_tokens SET used_at = :now WHERE id = :id')->execute([
            ':now' => $now,
            ':id' => (int)$tok['id'],
        ]);
        $pdo->prepare('UPDATE users SET password_hash = :ph, updated_at = :now WHERE id = :uid')->execute([
            ':ph' => $ph,
            ':now' => $now,
            ':uid' => $userId,
        ]);
        $pdo->commit();
    } catch (Throwable) {
        $pdo->rollBack();
        return ['ok' => false, 'errors' => ['Error during password update.']];
    }

    return ['ok' => true];
}

