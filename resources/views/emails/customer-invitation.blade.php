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
        .content ul {
            margin: 12px 0 20px 0;
            padding-left: 20px;
        }
        .content li {
            margin-bottom: 8px;
        }
        .content h2 {
            font-size: 16px;
            margin: 26px 0 6px 0;
            color: #2c3e50;
        }
    </style>
</head>
<body>
    <div class="container">
        <div style="background:#ffffff; text-align:center; padding:20px 30px;">
            <img src="{{ asset('storage/img/Logo.png') }}" alt="Slagerij Louman" style="max-height:80px; max-width:240px;">
        </div>
        <div class="header">
            <h1>Nieuw bij Slagerij Louman</h1>
            <p>Uw bestellingen voortaan eenvoudig online doorgeven</p>
        </div>

        <div class="content">
            <p>Beste relatie,</p>

            <p>
                Met trots willen wij u informeren dat de nieuwe website van Slagerij Louman live staat.
                Naast een vernieuwde uitstraling hebben wij een extra toevoeging gelanceerd:
                het nieuwe B2B Klantportaal.
            </p>

            <p>Via het klantportaal kunt u eenvoudig:</p>

            <ul>
                <li>Online uw wekelijkse of maandelijkse bestellingen plaatsen</li>
                <li>Aangeven of u zichtbaar wilt zijn als officieel verkooppunt op de website van Louman</li>
            </ul>

            <p>
                Wij hebben reeds een account voor u aangemaakt in het Slagerij Louman B2B klantportaal.
                Klik op de knop hieronder om een wachtwoord in te stellen en uw registratie af te ronden.
            </p>

            <div class="button-box">
                <a href="{{ $acceptUrl }}" class="button">Account aanmaken</a>
            </div>

            <h2>Belangrijk om te weten:</h2>

            <ul>
                <li>
                    De getoonde prijzen in het klantportaal zijn onze standaardprijzen. Uw persoonlijk
                    afgesproken prijzen worden bij de facturatie automatisch toegepast en zijn terug te
                    zien op uw factuur.
                </li>
                <li>Uw huidige prijsafspraken blijven ongewijzigd</li>
                <li>Leverdagen en levertijden blijven hetzelfde</li>
                <li>Facturatie blijft verlopen zoals u gewend bent</li>
            </ul>

            <p>
                Met dit nieuwe portaal willen wij het bestellen eenvoudiger, overzichtelijker en sneller
                maken voor onze zakelijke klanten.
            </p>

            <p>
                Wij hopen u hiermee nog beter van dienst te kunnen zijn en kijken uit naar een mooie
                voortzetting van onze samenwerking.
            </p>

            <p>
                Met vriendelijke groet,<br>
                Team Slagerij Louman
            </p>

            <p class="expiry">
                Deze uitnodiging is geldig tot {{ $expiresAt->format('d-m-Y') }}.
            </p>
        </div>

        <div class="footer">
            <strong>Worstmakerij T.F.M. Louman</strong><br>
            Kombuisweg 15<br>
            1041 AV Amsterdam<br>
            Tel: 020 4470930 &bull; info@louman-jordaan.nl
        </div>
    </div>
</body>
</html>
