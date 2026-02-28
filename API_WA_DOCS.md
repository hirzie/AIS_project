# Dokumentasi Integrasi WhatsApp API (AIS - CRM)

Dokumen ini menjelaskan spesifikasi teknis dan cara integrasi antara **AIS Portal** (Backend PHP) dan **CRM WhatsApp Gateway** (Node.js).

---

## 1. Overview
Integrasi ini memungkinkan AIS Portal untuk mengirimkan notifikasi WhatsApp secara otomatis (misalnya notifikasi keamanan, presensi, dll) menggunakan gateway WhatsApp yang berjalan di CRM.

- **Frontend AIS**: Mengirim request trigger ke Backend AIS.
- **Backend AIS**: Memproses data, memformat nomor HP, dan meneruskan request ke API CRM.
- **CRM Gateway**: Menerima request dan mengirim pesan WhatsApp via library `whatsapp-web.js`.

---

## 2. Konfigurasi di AIS Portal
Untuk mengaktifkan fitur ini, admin harus mengisi pengaturan di modul **Settings / Integrasi WhatsApp**:

1.  **URL API**:
    *   **Local-to-Local**: `http://localhost:3004/api/whatsapp/send`
    *   **Hosting-to-Hosting**: `https://crm.creativedivisions.my.id/api/whatsapp/send`
2.  **Token (Client ID)**:
    *   Isi dengan `ais` (sesuai dengan session ID yang dibuat di CRM).
3.  **Nomor Target**:
    *   Nomor HP tujuan untuk notifikasi test/security (contoh: `081234567890`).

---

## 3. Spesifikasi API CRM
Gateway CRM menyediakan endpoint REST API untuk mengirim pesan.

### Endpoint: Send Message
*   **URL**: `/api/whatsapp/send`
*   **Method**: `POST`
*   **Headers**: `Content-Type: application/json`

#### Request Body (Payload)
```json
{
  "to": "6281234567890@c.us",
  "message": "Halo, ini pesan uji coba dari AIS.",
  "clientId": "ais"
}
```

| Parameter | Tipe | Wajib | Keterangan |
| :--- | :--- | :--- | :--- |
| `to` | String | Ya | Nomor tujuan format internasional (`62...`). Akhiran `@c.us` opsional (akan ditambahkan otomatis jika kurang). |
| `message` | String | Ya | Isi pesan teks yang akan dikirim. |
| `clientId` | String | Ya | ID Session WhatsApp (sesuai Token di pengaturan AIS). Default: `umrah`. |

#### Response
**Sukses:**
```json
{
  "success": true,
  "data": { ... }
}
```

**Gagal:**
```json
{
  "success": false,
  "error": "Deskripsi error (misal: Client not ready)"
}
```

---

## 4. Implementasi Code

### Backend PHP (`AIS/api/security.php`)
Fungsi `sendWhatsAppMessage` menangani formatting dan pengiriman HTTP Request.

```php
function sendWhatsAppMessage($pdo, $text, $targetOverride = null) {
    // 1. Ambil Konfigurasi
    $url = trim((string)getSetting($pdo, 'wa_api_url'));
    $token = trim((string)getSetting($pdo, 'wa_api_token'));
    $target = $targetOverride ?: trim((string)getSetting($pdo, 'wa_security_target'));

    if ($url === '' || $token === '' || $target === '' || $text === '') {
        return ['success' => false, 'error' => 'Konfigurasi tidak lengkap'];
    }

    // 2. Format Nomor HP (08xx -> 628xx@c.us)
    $target = preg_replace('/[^0-9]/', '', $target);
    if (substr($target, 0, 1) === '0') {
        $target = '62' . substr($target, 1);
    }
    if (strpos($target, '@') === false) {
        $target .= '@c.us';
    }

    // 3. Kirim Request
    $payload = json_encode([
        'to' => $target,
        'message' => $text,
        'clientId' => $token
    ]);

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 20,
            'ignore_errors' => true
        ],
        'ssl' => ['verify_peer' => false, 'verify_peer_name' => false]
    ]);

    $resp = @file_get_contents($url, false, $context);
    // ... handle response ...
}
```

### Frontend JavaScript (`AIS/assets/js/modules/admin.js`)
Mixin Vue.js untuk memicu pengiriman pesan test.

```javascript
async testWaNotification() {
    try {
        // ... get baseUrl ...
        const res = await fetch(baseUrl + 'api/security.php?action=send_wa_test', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
                message: this.waTestMessage, 
                target: this.schoolSettings.wa_security_target 
            })
        });
        // ... handle response ...
    } catch (e) {
        console.error(e);
    }
}
```

---

## 5. Troubleshooting

### 1. Error: `SyntaxError: Unexpected token < in JSON`
*   **Penyebab**: Backend PHP mengembalikan error HTML (bukan JSON), biasanya karena warning PHP masuk ke output.
*   **Solusi**: Pastikan `ob_start()` ada di baris pertama file PHP dan gunakan `ob_end_clean()` sebelum `echo json_encode(...)`.

### 2. Error: `Failed to send message`
*   **Penyebab**: Client WhatsApp di CRM belum siap atau belum scan QR.
*   **Solusi**: Cek dashboard CRM, pastikan status client `ais` adalah **Online**.

### 3. Pesan tidak masuk
*   **Penyebab**: Format nomor salah atau Token tidak cocok.
*   **Solusi**: Pastikan nomor diawali `62` (bukan `08`) dan Token di AIS sama persis dengan `clientId` di CRM.

---
*Dokumen dibuat otomatis oleh AI Assistant untuk keperluan pengembangan.*
