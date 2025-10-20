<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <title>Pembayaran Midtrans</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}" />
  <style>
    body { font-family: Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 0; }
    .container { display: flex; flex-direction: column; justify-content: center; align-items: center; min-height: 100vh; padding: 20px; }
    .card { display: grid; grid-template-columns: 1fr 1fr; width: 100%; max-width: 800px; background-color: #fff; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); overflow: hidden; }
    @media (max-width: 768px) { .card { grid-template-columns: 1fr; } }
    .left, .right { padding: 30px; }
    .left h2 { font-size: 24px; margin-bottom: 10px; color: #333; }
    .left p { color: #777; margin-bottom: 25px; }
    label { display: block; margin-bottom: 5px; font-weight: 500; color: #444; }
    input { width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #ddd; border-radius: 6px; font-size: 15px; }
    .right { background-color: #4f46e5; color: white; display: flex; flex-direction: column; justify-content: space-between; }
    .right h3 { font-size: 20px; margin-bottom: 20px; }
    .btn { width: 100%; padding: 12px; border: none; border-radius: 8px; background-color: rgba(255, 255, 255, 0.2); color: white; font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.2s; margin-bottom: 10px; }
    .btn:hover { background-color: rgba(255, 255, 255, 0.3); transform: scale(1.02); }
    #result { margin-top: 20px; text-align: center; font-size: 15px; }
    .loading { display: flex; flex-direction: column; align-items: center; }
    .loading-spinner { border: 4px solid rgba(255, 255, 255, 0.3); border-top: 4px solid white; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin-bottom: 10px; }
    @keyframes spin { 100% { transform: rotate(360deg); } }
    .error { background-color: rgba(255, 0, 0, 0.3); padding: 10px; border-radius: 5px; color: white; }
    .va-box { background-color: rgba(255, 255, 255, 0.2); padding: 15px; border-radius: 8px; }
    #qrcode { display: flex; justify-content: center; margin-top: 20px; }
    .pay-link { display: inline-block; margin-top: 15px; background: white; color: #4f46e5; padding: 10px 20px; border-radius: 6px; text-decoration: none; font-weight: bold; }
    .pay-link:hover { background: #e0e7ff; }
  </style>

 
  <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="left">
        <h2>Detail Pembayaran</h2>
        <p>Isi detail di bawah untuk melanjutkan.</p>

        <label>Nama Lengkap</label>
        <input id="name" type="text" value="" />

        <label>Email</label>
        <input id="email" type="email" value="" />

        <label>Jumlah (IDR)</label>
        <input id="amount" type="number" value="20000" min="1000" />
      </div>

      <div class="right">
        <div>
          <h3>Pilih Metode Pembayaran</h3>
          <button class="btn" onclick="pay('gopay')">GoPay / QRIS</button>
        </div>
        <div id="result"></div>
      </div>
    </div>
  </div>

  <script>
    async function pay(type) {
      const resultDiv = document.getElementById("result");
      const name = document.getElementById("name").value.trim();
      const email = document.getElementById("email").value.trim();
      const amount = parseInt(document.getElementById("amount").value);

      if (!name || !email || !amount || amount < 10000) {
        resultDiv.innerHTML = `<div class="error">Mohon isi semua Form dengan benar. Minimum pembayaran 10000.</div>`;
        return;
      }

      resultDiv.innerHTML = `
        <div class="loading">
          <div class="loading-spinner"></div>
          <span>Memproses pembayaran...</span>
        </div>
      `;

      try {
        const res = await fetch(window.location.origin + "/payment/process", {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content,
          },
          body: JSON.stringify({
            name,
            email,
            amount,
            payment_type: type,
          }),
        });

        const data = await res.json();
        console.log("=== Payment Response ===", data);

        if (data.status === "success" && type === "gopay") {
          const deeplink = data.data.actions.find((a) => a.name === "deeplink-redirect");
          if (deeplink && deeplink.url) {
            resultDiv.innerHTML = `
              <p><b>Scan QR Code untuk bayar GoPay / QRIS</b></p>
              <div id="qrcode"></div>
              <a class="pay-link" href="${deeplink.url}" target="_blank">Bayar Sekarang</a>
            `;
            new QRCode(document.getElementById("qrcode"), {
              text: deeplink.url,
              width: 200,
              height: 200,
            });
          } else {
            resultDiv.innerHTML = `<div class="error">Link pembayaran GoPay tidak tersedia.</div>`;
          }
        } else if (data.status !== "success") {
          resultDiv.innerHTML = `<div class="error"><b>Error:</b> ${
            data.status_message || "Gagal memproses pembayaran."
          }</div>`;
        }
      } catch (err) {
        console.error("⚠️ Error:", err);
        resultDiv.innerHTML = `<div class="error">Minimal Pembayaran 10000 </div>`;
      }
    }
  </script>
</body>
</html>
