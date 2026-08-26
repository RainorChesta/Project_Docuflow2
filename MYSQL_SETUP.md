# 🗄️ MySQL Database & User Setup Guide for DokuFlow

Panduan ini berisi perintah SQL untuk membuat database **`db_dokuflow`** dan user remote **`dokuflow_executive_remote`** dengan hak akses minimal yang aman (Principle of Least Privilege) khusus pada database `db_dokuflow`.

---

## 🔒 Hak Akses (Privileges) yang Dibutuhkan Laravel

Untuk menjalankan aplikasi Laravel secara penuh (termasuk *migration* dan operasi CRUD), user **TIDAK memerlukan** `ALL PRIVILEGES` atau akses administratif server (`SUPER`, `FILE`, `GRANT OPTION`).

Privikasi penting yang diberikan meliputi:

| Kategori | Hak Akses (Privilege) | Kegunaan dalam Laravel |
| :--- | :--- | :--- |
| **Operasi Data (DML)** | `SELECT` | Membaca data dari tabel |
| | `INSERT` | Menambahkan data baru |
| | `UPDATE` | Memperbarui data yang ada |
| | `DELETE` | Menghapus data/record |
| **Struktur Tabel (DDL)**| `CREATE` | Membuat tabel baru (saat `php artisan migrate`) |
| | `ALTER` | Mengubah struktur tabel / tambah kolom |
| | `DROP` | Menghapus tabel/kolom (saat rollback / drop table) |
| | `INDEX` | Membuat dan mengelola indeks query |
| | `REFERENCES` | Membuat constraint *Foreign Key* antar tabel |

---

## 🛠️ Step 1: Login ke Server MySQL

Masuk ke server MySQL menggunakan user `root` (atau user admin MySQL):

```bash
mysql -u root -p
```

---

## 📜 Step 2: Perintah SQL Setup Database & User

Jalankan perintah SQL berikut di MySQL Console:

```sql
-- 1. Buat Database db_dokuflow dengan Charset utf8mb4
CREATE DATABASE IF NOT EXISTS `db_dokuflow` 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

-- 2. Buat User Remote 'dokuflow_executive_remote'
CREATE USER IF NOT EXISTS 'dokuflow_executive_remote'@'%' 
  IDENTIFIED BY 'RAj65eXErowu!';

-- 3. Berikan HANYA Hak Akses yang Diperlukan (SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES)
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES 
  ON `db_dokuflow`.* 
  TO 'dokuflow_executive_remote'@'%';

-- 4. Refresh Hak Akses (Flush Privileges)
FLUSH PRIVILEGES;
```

---

## 🔍 Step 3: Verifikasi User & Hak Akses

Untuk memastikan hak akses yang diberikan sesuai:

```sql
SHOW GRANTS FOR 'dokuflow_executive_remote'@'%';
```

*Ekspektasi Output:*
```sql
GRANT USAGE ON *.* TO `dokuflow_executive_remote`@`%`
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, REFERENCES ON `db_dokuflow`.* TO `dokuflow_executive_remote`@`%`
```

---

## ⚙️ Step 4: Update Konfigurasi `.env` pada DokuFlow

Setelah database dan user berhasil dibuat, perbarui file `.env` di proyek DokuFlow (`/home/austin/Web Dev/Projects/dokuflow/dokuflow-project/.env`):

```env
DB_CONNECTION=mysql
DB_HOST=202.10.46.4
DB_PORT=3306
DB_DATABASE=db_dokuflow
DB_USERNAME=dokuflow_executive_remote
DB_PASSWORD=RAj65eXErowu!
```

---

## 🚀 Step 5: Jalankan Migration Database

Jalankan perintah migration dari root proyek `dokuflow-project`:

```bash
php artisan migrate
```
