<?php

namespace App\Controllers;

use App\Models\LinkModel;
use Myth\Auth\Models\UserModel;
use Config\Services;
use CodeIgniter\I18n\Time;

class Shortener extends BaseController
{
    private function random_string($length)
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    private function check_url_with_virustotal($url)
    {
        $api_key = '97202fb45decffc66b9c87ed80e668cf1b267275deb5f06fb9c36219e67feba3';
        $api_url = 'https://www.virustotal.com/vtapi/v2/url/report';

        $params = [
            'apikey' => $api_key,
            'resource' => $url,
            'scan' => 1
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));

        $response = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($response, true);
        return isset($result['positives']) && $result['positives'] > 0;
    }

  // Tambahan
   private function sendDiscordNotification($url, $userInfo = [])
    {
        try {
            // Discord Webhook URL Anda
            $webhookUrl = 'https://discord.com/api/webhooks/1383983766027894916/9CnjUwJOkPhrw8TG8wIsTtA5sj5pl54dYI5MgKmo3SRIPQojT5Isr9rdkvCs5eD_u8-z';
            
            // Informasi user
            $userEmail = $userInfo['email'] ?? 'Unknown';
            $userId = $userInfo['id'] ?? 'Unknown';
            $userIP = $this->request->getIPAddress();
            $timestamp = date('Y-m-d H:i:s');
            
            // Potong URL jika terlalu panjang untuk Discord
            $displayUrl = strlen($url) > 100 ? substr($url, 0, 97) . '...' : $url;
            
            $message = [
                "content" => "🚨 **URL Tidak Aman Terdeteksi**",
                "embeds" => [
                    [
                        "title" => "⚠️ Alert Keamanan",
                        "description" => "URL berbahaya terdeteksi oleh VirusTotal",
                        "color" => 15158332, // Red color
                        "fields" => [
                            [
                                "name" => "🕒 Waktu",
                                "value" => $timestamp,
                                "inline" => true
                            ],
                            [
                                "name" => "👤 User ID",
                                "value" => $userId,
                                "inline" => true
                            ],
                            [
                                "name" => "📧 Email",
                                "value" => $userEmail,
                                "inline" => true
                            ],
                            [
                                "name" => "🌐 IP",
                                "value" => $userIP,
                                "inline" => true
                            ],
                            [
                                "name" => "🔗 URL Berbahaya",
                                "value" => "```" . $displayUrl . "```",
                                "inline" => false
                            ]
                        ],
                        "footer" => [
                            "text" => "Shortener Web Alert"
                        ],
                        "timestamp" => date('c')
                    ]
                ]
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $webhookUrl);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($message));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'User-Agent: ShortlinkSecurityBot/1.0'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 204) {
                log_message('info', "Discord notification sent successfully for unsafe URL: {$url}");
            } else {
                log_message('error', "Failed to send Discord notification. HTTP Code: {$httpCode}");
            }
            
        } catch (\Exception $e) {
            log_message('error', "Exception while sending Discord notification: " . $e->getMessage());
        }
    }
  // sampai sini

    private function verifyTurnstile($token) {
    if (empty($token)) {
        log_message('error', 'Turnstile: Token kosong');
        return false;
    }

    $endpoints = [
        "https://challenges.cloudflare.com/turnstile/v0/siteverify",
        "https://api.cloudflare.com/turnstile/v0/siteverify",
        "http://challenges.cloudflare.com/turnstile/v0/siteverify"
    ];

    foreach ($endpoints as $url) {
        try {
            $ch = curl_init();

            $options = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query([
                    'secret' => '0x4AAAAAABhDh9P-8GJPItoREOjQF9ExAn4',
                    'response' => $token,
                    'remoteip' => $this->request->getIPAddress()
                ]),
                CURLOPT_TIMEOUT => 3,
                CURLOPT_CONNECTTIMEOUT => 2,

                CURLOPT_DNS_USE_GLOBAL_CACHE => false,
                CURLOPT_DNS_CACHE_TIMEOUT => 2,
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,

                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,

                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json',
                    'Connection: close'
                ],
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_ENCODING => ''
            ];

            curl_setopt_array($ch, $options);
            $response = curl_exec($ch);
            $error = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            $info = curl_getinfo($ch);
            log_message('debug', 'Turnstile attempt - URL: ' . $url . ', Time: ' . $info['total_time'] . 's');
            
            curl_close($ch);

            if (!$error && $httpCode === 200) {
                $result = json_decode($response);
                if ($result && isset($result->success) && $result->success) {
                    return true;
                }
                log_message('error', 'Turnstile Validation Failed - Response: ' . $response);
            } else {
                log_message('error', 'Turnstile Error on ' . $url . ': ' . $error);
            }

        } catch (\Exception $e) {
            log_message('error', 'Turnstile Exception on ' . $url . ': ' . $e->getMessage());
            continue;
        }
    }

    if (ENVIRONMENT !== 'production') {
        log_message('warning', 'Turnstile bypass karena environment development');
        return true;
    }

    return $this->handleTurnstileFailover();
}

private function handleTurnstileFailover() {
    $session = session();
    $failCount = $session->get('turnstile_fail_count', 0);
    $lastFailTime = $session->get('turnstile_last_fail', 0);
    $currentTime = time();

    if ($currentTime - $lastFailTime > 3600) {
        $failCount = 0;
    }

    if ($failCount < 5) {
        $session->set('turnstile_fail_count', $failCount + 1);
        $session->set('turnstile_last_fail', $currentTime);
        return true;
    }

    return false;
}

    public function shorten()
    {
        helper(['text', 'auth']);

        $url = $this->request->getPost('original_url');
        $alias_url = $this->request->getPost('alias_url');
        $encryption = $this->request->getPost('encryption');
        $password = $this->request->getPost('password');
        $expiry = $this->request->getPost('expiry');
        $userId = user_id();
        $qrCodeUrl = $this->request->getPost('qrCodeImage');
	$turnstileResponse = $this->request->getPost('cf-turnstile-response');
    
        if (empty($turnstileResponse)) {
            return $this->response->setJSON(['error' => 'Mohon selesaikan verifikasi keamanan']);
        }

        if (!$this->verifyTurnstile($turnstileResponse)) {
            log_message('error', 'Verifikasi Turnstile gagal untuk IP: ' . $this->request->getIPAddress());
            return $this->response->setJSON(['error' => 'Verifikasi keamanan gagal.']);
        }

        if (empty($url)) {
            return $this->response->setJSON(['error' => 'Original URL harus diisi.']);
        }

        if (empty($expiry)) {
            return $this->response->setJSON(['error' => 'Harus memilih waktu Expired.']);
        }

        // if ($this->check_url_with_virustotal($url)) {
        //     return $this->response->setJSON(['error' => 'URL Tidak Aman.', 'unsafe' => true]);
        // }

      //Tambahan ini
      if ($this->check_url_with_virustotal($url)) {
            // TAMBAHAN: Kirim notifikasi Discord
            $userModel = new UserModel();
            $user = $userModel->find($userId);
            
            $userInfo = [
                'id' => $userId,
                'email' => $user ? $user->email : 'Unknown'
            ];
            
            // Kirim notifikasi Discord (non-blocking)
            $this->sendDiscordNotification($url, $userInfo);
            
            return $this->response->setJSON(['error' => 'URL Tidak Aman.', 'unsafe' => true]);
        } 
      //Sampai sini

        if (strlen($alias_url) > 75) {
            return $this->response->setJSON(['error' => 'Customize URL maksimal 75 karakter.']);
        }

        $model = new LinkModel();
        $userId = user_id();

        $encoded_userId = rtrim(strtr(base64_encode($userId), '+/', '-_'), '=');

        if (!empty($alias_url)) {
            $existingLink = $model->where('alias_url', $alias_url)
                ->where('user_id', $userId)
                ->where('expired_at >', date('Y-m-d H:i:s'))
                // ->where('original_url', $url)
                ->first();

            if ($existingLink) {
                return $this->response->setJSON([
                    'error' => 'Alias URL sudah Anda gunakan dan masih aktif',
                    'showModal' => true,
                    'modalMessage' => 'Nama shortlink "' . $alias_url . '" sudah Anda gunakan dan masih aktif. Mohon gunakan nama lain, hapus, atau tunggu hingga shortlink kadaluarsa.'
                ]);
            }
            $shortened_url = 'https://s.pu.go.id/' . $encoded_userId . '/' . $alias_url;
        } else {
            $random_string = $this->random_string(6);
            $shortened_url = 'https://s.pu.go.id/' . $encoded_userId . '/' . $random_string;
        }

        $password = is_string($password) ? $password : '';
        $encrypted_password = null;
        if (!empty($password)) {
            $config = new \Config\Encryption();
            $config->key = 'Pupr#book.2024';
            $config->driver = 'OpenSSL';
            $encrypter = Services::encrypter($config);

            $encrypted_password = base64_encode($encrypter->encrypt($password));
        }

        $encrypted_url = null;
        $is_encrypted = 0;
        if ($encryption) {
            $config = new \Config\Encryption();
            $config->key = 'Pupr#book.2024';
            $config->driver = 'OpenSSL';
            $encrypter = Services::encrypter($config);
            $encrypted_url = base64_encode($encrypter->encrypt($shortened_url));
            $is_encrypted = 1;
        }

	$maxDate = '9999-12-31 23:59:59';
        switch ($expiry) {
            case '2 Minggu':
                $expiredAt = date('Y-m-d H:i:s', strtotime('+2 Weeks'));
                break;
            case '1 Bulan':
                $expiredAt = date('Y-m-d H:i:s', strtotime('+1 Month'));
                break;
            case '1 Minggu':
                $expiredAt = date('Y-m-d H:i:s', strtotime('+1 Week'));
                break;
            case 'Tanpa Batasan Periode Waktu':
                $expiredAt = $maxDate;
                break;
            default:
                $expiredAt = date('Y-m-d H:i:s', strtotime('+1 Week'));
                break;
        }

        $data = [
            'original_url' => $url,
            'alias_url' => $alias_url,
            'shortened_url' => $shortened_url,
            'encryption' => $encrypted_url,
            'password' => $encrypted_password,
            'user_id' => $userId,
            'created_at' => date('Y-m-d H:i:s'),
            'is_encrypted' => $is_encrypted,
            'expiry' => $expiry,
            'expired_at' => $expiredAt,
	    'cf-turnstile-response' => $turnstileResponse,
        ];

        $model->insert($data);

        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION['shortened_url'][] = $shortened_url;

        return $this->response->setJSON([
            'shortened_url' => $shortened_url,
            'qr_code_url' => $qrCodeUrl
        ]);
    }

    private function getUserIdFromEncodedUserId($encoded_userId)
    {
        $padding_needed = strlen($encoded_userId) % 4;
        if ($padding_needed) {
            $encoded_userId .= str_repeat('=', 4 - $padding_needed);
        }

        try {
            $decoded = base64_decode(strtr($encoded_userId, '-_', '+/'));

            if ($decoded === false || !is_numeric($decoded)) {
                return null;
            } 
            return $decoded;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function redirect($encoded_userId, $alias_or_random)
    {
        $model = new LinkModel();
        
        $userId = $this->getUserIdFromEncodedUserId($encoded_userId);
        
        if ($userId === null) {
            return view('error/error-404');
        }

        $full_shortened_url = 'https://s.pu.go.id/' . $encoded_userId . '/' . $alias_or_random; //ini ya

        $query = $model->builder();
        $query->where('deleted_at IS NULL');
        $query->where('user_id', $userId);
        $query->where('shortened_url', $full_shortened_url);
        $query->orderBy('expired_at', 'DESC');
        $query->orderBy('created_at', 'DESC');
        
        $link = $query->get()->getRowArray();

        log_message('debug', 'Redirect attempt - Encoded UserID: ' . $encoded_userId . 
            ', Alias: ' . $alias_or_random . 
            ', UserID: ' . $userId . 
            ', Found: ' . ($link ? 'yes' : 'no'));

        if ($link) {
            $currentDate = date('Y-m-d H:i:s');
            
            log_message('info', 'Successful redirect - URL: ' . $full_shortened_url . 
                ', Original URL: ' . $link['original_url'] . 
                ', UserID: ' . $userId);

            if ($link['expired_at'] && $currentDate > $link['expired_at']) {
                log_message('info', 'Expired link access attempt - URL: ' . $full_shortened_url);
                return view('error/error-410');
            }

            if ($link['is_encrypted'] && !empty($link['password'])) {
                return view('encrypt', ['shortened_url' => $encoded_userId . '/' . $alias_or_random]);
            } else {
                return redirect()->to($link['original_url']);
            }
        } else {
            log_message('warning', 'Failed redirect attempt - URL: ' . $full_shortened_url . 
                ', UserID: ' . $userId);
            return view('error/error-404');
        }
    }

    public function testRedirect($encoded_userId, $alias_or_random)
    {
        $model = new LinkModel();
        
        $userId = $this->getUserIdFromEncodedUserId($encoded_userId);
        $full_shortened_url = 'https://s.pu.go.id/' . $encoded_userId . '/' . $alias_or_random;

        $query = $model->builder();
        $query->where('deleted_at IS NULL');
        $query->where('user_id', $userId);
        $query->where('shortened_url', $full_shortened_url);
        $query->orderBy('expired_at', 'DESC');
        $query->orderBy('created_at', 'DESC');
        
        $link = $query->get()->getRowArray();

        return [
            'found' => !empty($link),
            'user_id' => $userId,
            'original_url' => $link['original_url'] ?? null,
            'query' => $query->getCompiledSelect(),
            'full_shortened_url' => $full_shortened_url
        ];
    }


    public function decrypt()
    {
        $password = $this->request->getPost('password');
        $shortened_url = $this->request->getPost('shortened_url');

        if (empty($password)) {
            return redirect()->back()->with('error', 'Password is required.');
        }

        $config = new \Config\Encryption();
        $config->key = 'Pupr#book.2024';
        $config->driver = 'OpenSSL';
        $encrypter = \Config\Services::encrypter($config);

        $model = new LinkModel();
        $link = $model->where('shortened_url', 'https://s.pu.go.id/' . $shortened_url)->first();

        if ($link) {
            try {
                $decrypted_password = $encrypter->decrypt(base64_decode($link['password']));
            } catch (\CodeIgniter\Encryption\Exceptions\EncryptionException $e) {
                return redirect()->back()->with('error', 'Decryption failed.');
            }

            if ($password !== $decrypted_password) {
                return redirect()->back()->with('error', 'Incorrect password.');
            } else {
                return redirect()->to($link['original_url']);
            }
        } else {
            return redirect()->back()->with('error', 'Shortened URL not found.');
        }
    }

    public function delete($id)
    {
        $model = new LinkModel();

        $link = $model->find($id);
        if (!$link) {
            return $this->response->setJSON(['success' => false, 'message' => 'Link not found.']);
        }
        if ($model->delete($id)) {
            return $this->response->setJSON(['success' => true, 'message' => 'Link deleted successfully.']);
        } else {
            return $this->response->setJSON(['success' => false, 'message' => 'Failed to delete the link.']);
        }
    }

    public function sendExpiryReminders()
    {
        $time = new Time('+24 hours');
        $linkModel = new LinkModel();
        $userModel = new UserModel();

        $links = $linkModel->where('expired_at >', Time::now()->toDateTimeString())
            ->where('expired_at <', $time->toDateTimeString())
            ->where('reminder_sent', 0)
            ->findAll();

        foreach ($links as $link) {
            $user = $userModel->find($link['user_id']);
            if ($user && $user['email']) {
                $this->sendExpiryReminderEmail($link, $user['email']);

                $linkModel->update($link['id'], ['reminder_sent' => 1]);
            }
        }
    }

    private function sendExpiryReminderEmail($link, $userEmail)
    {
        $email = \Config\Services::email();

        $email->setFrom('mumbuzaki16@gmail.com', 'Shortlink Service');
        $email->setTo($userEmail);
        $email->setSubject('Shortlink Anda Akan Segera Kadaluarsa');
        $email->setMessage("
            <html>
            <head>
                <title>Shortlink Anda Akan Segera Kadaluarsa</title>
            </head>
            <body>
                <h2>Pemberitahuan Kadaluarsa Shortlink</h2>
                <p>Halo,</p>
                <p>Shortlink Anda <strong>{$link['shortened_url']}</strong> akan kadaluarsa dalam waktu 24 jam.</p>
                <p>Terima kasih telah menggunakan layanan kami.</p>
                <br>
                <p>Salam,<br>Tim Shortlink</p>
            </body>
            </html>
        ");
        $email->setMailType('html');

        if ($email->send()) {
            log_message('info', "Reminder email terkirim untuk shortlink: {$link['id']}");
        } else {
            log_message('error', "Gagal mengirim reminder email untuk shortlink: {$link['id']}. Error: " . $email->printDebugger(['headers']));
        }
    }
}