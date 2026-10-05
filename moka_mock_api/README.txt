Ekstrak folder moka_mock_api ke C:\xampp\htdocs\ lalu nyalakan Apache.
Base URL: http://localhost/moka_mock_api
FT-01 POST /api/sync; token Bearer demo-token-123; body {"transaction_id":"TRX001","product_id":"P001","quantity":2}
FT-02 quantity 999
FT-03 GET /api/products/P999
FT-04 POST tanpa product_id
FT-05 quantity "dua"
FT-06 kirim TRX001 dua kali
FT-07 token salah
FT-08 GET /api/products?mock_status=429 atau 503 atau slow
