# Standar Input Data AIS

- Tujuan: mencegah duplikasi input pada jaringan tidak stabil, menjaga konsistensi, dan memastikan otorisasi tetap aman di server.

## Prinsip Umum

- Idempotensi: setiap submit memiliki request_id unik; pengiriman ulang dengan token yang sama tidak membuat record baru.
- Otorisasi server-side: semua write memakai $_SESSION; frontend hanya untuk UI.
- Transaksi: operasi multi-tabel dibungkus transaksi agar tidak partial write.
- Constraint DB: UNIQUE index pada request_id sebagai penjaga terakhir saat terjadi balapan request.

## Frontend (UI)

- Generate request_id saat submit:
  - Format disarankan: `<prefix>-<timestamp>-<USER_ID>-<random>` atau UUIDv4.
  - Kirim dalam payload ke API pada semua operasi create/save.
- Pencegahan klik ganda:
  - Disable tombol saat submit dan tampilkan loading.
- Retry aman:
  - Jika perlu retry karena jaringan, gunakan request_id yang sama agar server menganggapnya idempotent.

## API (Server)

- Terima request_id (opsional tapi disarankan) untuk operasi create/save.
- Pola umum:
  1) Jika request_id ada: SELECT id by request_id.
  2) Jika belum ada: INSERT dengan request_id.
  3) Jika INSERT gagal karena duplicate key: SELECT ulang id yang ada dan kembalikan sebagai sukses (duplicate ignored).
- Semua write yang memodifikasi beberapa tabel harus memakai:
  - $pdo->beginTransaction()
  - write/insert/update
  - $pdo->commit(), atau $pdo->rollBack() jika gagal.
- Otorisasi:
  - officer_user_id / user_id diambil dari $_SESSION, bukan dari request body.

## Database

- Tambah kolom request_id VARCHAR(64) dan UNIQUE index pada tabel yang berpotensi duplikat:
  - Contoh aktif:
    - security_checklist_runs.request_id (unik) — [api/security.php](file:///c:/xampp/htdocs/AIS/api/security.php#L93-L110)
    - zero_reports.request_id (unik) — [api/zero_report.php](file:///c:/xampp/htdocs/AIS/api/zero_report.php#L9-L20)
    - fin_transactions.request_id (unik) — [api/finance.php](file:///c:/xampp/htdocs/AIS/api/finance.php#L15-L25)
    - fin_cash_advances.request_id (unik) — [api/finance.php](file:///c:/xampp/htdocs/AIS/api/finance.php#L26-L35)
    - fin_student_savings.request_id (unik) — [api/finance.php](file:///c:/xampp/htdocs/AIS/api/finance.php#L36-L45)
- Penamaan index:
  - Gunakan nama konsisten (mis. `uniq_request_id`, `uniq_zero_request`).
- Migrasi:
  - Jalankan alter saat sepi; proses cepat untuk ukuran tabel normal.

## Contoh Implementasi

- Security Checklist:
  - UI kirim request_id saat save — [modules/security/index.php](file:///c:/xampp/htdocs/AIS/modules/security/index.php#L1543-L1546)
  - API idempotent di start & save — [api/security.php](file:///c:/xampp/htdocs/AIS/api/security.php#L476-L496), [api/security.php](file:///c:/xampp/htdocs/AIS/api/security.php#L497-L521)
- Zero Report:
  - API menerima request_id dan mengabaikan duplikat — [api/zero_report.php](file:///c:/xampp/htdocs/AIS/api/zero_report.php#L80-L106)
- Finance (Keuangan):
  - Endpoint yang menerima request_id:
    - save_expense — [api/finance.php](file:///c:/xampp/htdocs/AIS/api/finance.php#L793)
    - pay_bill — [api/finance.php](file:///c:/xampp/htdocs/AIS/api/finance.php#L573)
    - save_savings — [api/finance.php](file:///c:/xampp/htdocs/AIS/api/finance.php#L735)
    - create_cash_advance — [api/finance.php](file:///c:/xampp/htdocs/AIS/api/finance.php#L1616)
    - record_expense_from_advance — [api/finance.php](file:///c:/xampp/htdocs/AIS/api/finance.php#L1679-L1752)
    - record_expense_from_proposal — [api/finance.php](file:///c:/xampp/htdocs/AIS/api/finance.php#L1805-L1852)
    - save_pos_transaction — [api/finance.php](file:///c:/xampp/htdocs/AIS/api/finance.php#L1376)
  - Catatan: jurnal hanya dibuat saat insert baru (bukan duplikat) agar tidak terjadi penggandaan pencatatan.

## Snippet Siap Pakai

- UI: generator request_id dan pemakaian pada fetch

```javascript
function generateRequestId(prefix = 'req') {
  const ts = Date.now();
  const uid = String(window.USER_ID || '');
  const rnd = Math.random().toString(36).slice(2);
  return `${prefix}-${ts}-${uid}-${rnd}`;
}

// Contoh pemakaian pada submit jurnal keuangan
const payload = {
  account_id: selAccountId,
  amount: Number(amount),
  memo: memoText,
  request_id: generateRequestId('fin-journal')
};
const r = await fetch(BASE_URL + 'api/finance.php?action=save_journal', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify(payload)
});
const j = await r.json();
```

- PHP API: pola idempotent create/save

```php
// Pastikan kolom request_id VARCHAR(64) dan UNIQUE INDEX di tabel target
// ALTER TABLE <table> ADD COLUMN request_id VARCHAR(64) DEFAULT NULL;
// ALTER TABLE <table> ADD UNIQUE INDEX uniq_request_id (request_id);

function insertIdempotent(PDO $pdo, array $data) {
    $reqId = isset($data['request_id']) ? trim((string)$data['request_id']) : '';
    $pdo->beginTransaction();
    try {
        if ($reqId !== '') {
            $s = $pdo->prepare("SELECT id FROM target_table WHERE request_id = ?");
            $s->execute([$reqId]);
            $found = (int)($s->fetchColumn() ?: 0);
            if ($found > 0) { $pdo->commit(); return $found; }
            try {
                $stmt = $pdo->prepare("INSERT INTO target_table (col1, col2, request_id) VALUES (?,?,?)");
                $stmt->execute([$data['col1'], $data['col2'], $reqId]);
                $id = (int)$pdo->lastInsertId();
                $pdo->commit();
                return $id;
            } catch (PDOException $e) {
                $s2 = $pdo->prepare("SELECT id FROM target_table WHERE request_id = ?");
                $s2->execute([$reqId]);
                $id = (int)($s2->fetchColumn() ?: 0);
                $pdo->commit();
                return $id;
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO target_table (col1, col2) VALUES (?,?)");
            $stmt->execute([$data['col1'], $data['col2']]);
            $id = (int)$pdo->lastInsertId();
            $pdo->commit();
            return $id;
        }
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
```

- Transaksi multi-tabel (contoh ringkas)

```php
$pdo->beginTransaction();
try {
    $jid = insertIdempotent($pdo, $journalData); // memiliki request_id
    $stmt = $pdo->prepare("INSERT INTO journal_lines (journal_id, account_id, amount) VALUES (?,?,?)");
    foreach ($lines as $ln) {
        $stmt->execute([$jid, $ln['account_id'], $ln['amount']]);
    }
    $pdo->commit();
    echo json_encode(['success' => true, 'data' => ['journal_id' => $jid]]);
} catch (Throwable $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Gagal simpan']);
}
```

## Checklist Penerapan per Modul

- [ ] Header modul dipakai (sesi diekspor: BASE_URL, USER_ROLE, USER_ID).
- [ ] UI generate request_id dan mengirimkannya ke API saat submit.
- [ ] API menerima request_id, menerapkan pola SELECT/INSERT/SELECT-on-duplicate.
- [ ] Tabel target memiliki kolom request_id + UNIQUE index.
- [ ] Penggunaan transaksi untuk write multi-tabel.
- [ ] Logging di activity_logs (opsional) untuk audit.
 - [ ] Finance: gunakan request_id pada semua operasi create/save (expense, bill payment, savings, cash advance, payout proposal, POS).

## Kasus Uji Wajib

- Submit normal → record dibuat.
- Klik ganda cepat → tetap satu record.
- Jaringan putus lalu retry (token sama) → tetap satu record.
- Dua request bersamaan dengan token sama → tetap satu record (menang oleh UNIQUE index).
- Payload tanpa request_id → tetap bekerja; dorong best-practice untuk memakai request_id.

## Catatan Keamanan

- Frontend tidak boleh menentukan officer_user_id/user_id; selalu dari $_SESSION.
- request_id tidak memberi hak akses; hanya mencegah duplikat.
- Validasi input tetap wajib (tipe, panjang, nilai yang diizinkan).
