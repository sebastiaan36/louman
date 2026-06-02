<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uitnodiging klantportaal</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            background: #2c3e50;
            color: #ffffff;
            padding: 40px 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0 0 8px 0;
            font-size: 24px;
        }
        .header p {
            margin: 0;
            opacity: 0.85;
            font-size: 15px;
        }
        .content {
            padding: 30px;
        }
        .button-box {
            text-align: center;
            margin: 30px 0;
        }
        .button {
            display: inline-block;
            background: #2c3e50;
            color: #ffffff !important;
            padding: 14px 30px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
        }
        .expiry {
            font-size: 13px;
            color: #888;
            text-align: center;
            margin-top: 20px;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px 30px;
            text-align: center;
            font-size: 12px;
            color: #888;
            border-top: 1px solid #e9ecef;
        }
        .footer strong { color: #555; }
    </style>
</head>
<body>
    <div class="container">
        <div style="background:#ffffff; text-align:center; padding:20px 30px;">
            <img src="{{ asset('storage/img/Logo.png') }}" alt="Slagerij Louman" style="max-height:80px; max-width:240px;">
        </div>
        <div class="header">
            <h1>Welkom bij Slagerij Louman</h1>
            <p>Maak je account aan voor het B2B klantportaal</p>
        </div>

        <div class="content">
            <p>Beste contactpersoon van <strong>{{ $companyName }}</strong>,</p>

            <p>
                We hebben een account voor je aangemaakt in het Slagerij Louman B2B klantportaal.
                Klik op de knop hieronder om een wachtwoord in te stellen en je registratie af te ronden.
            </p>

            <div class="button-box">
                <a href="{{ $acceptUrl }}" class="button">Account aanmaken</a>
            </div>

            <p class="expiry">
                Deze uitnodiging is geldig tot {{ $expiresAt->format('d-m-Y') }}.
            </p>
        </div>

        <div class="footer">
            <strong>Ambachtelijke Slagerij T.F.M. Louman</strong><br>
            Goudsbloemstraat 76 &bull; 1015 JR Amsterdam<br>
            Tel: 020 6220771 &bull; info@louman-jordaan.nl
        </div>
    </div>
</body>
</html>
