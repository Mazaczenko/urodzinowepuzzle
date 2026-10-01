<!DOCTYPE html>
<html lang="pl">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Telegram z kodem QR</title>
        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=special-elite:400|roboto-condensed:400,700&display=swap" rel="stylesheet" />

        {{-- A PRL-era telegram form: yellowed paper, red print, typewritten text and a rubber stamp. --}}
        <style>
            @page {
                size: A5 landscape;
                margin: 0;
            }

            :root {
                --paper: #efe4c2;
                --print: #b3362c;
                --ink: #23262e;
                --stamp: #5a3d8c;
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 20px;
                padding: 24px 16px;
                background: #2b2620 radial-gradient(ellipse at center, #3a332b, #1d1915);
                font-family: 'Roboto Condensed', 'Arial Narrow', sans-serif;
            }

            .toolbar {
                display: flex;
                gap: 10px;
                flex-wrap: wrap;
                justify-content: center;
            }

            .toolbar button,
            .toolbar a {
                font: 700 15px 'Roboto Condensed', sans-serif;
                letter-spacing: 0.04em;
                text-transform: uppercase;
                text-decoration: none;
                padding: 10px 18px;
                border-radius: 999px;
                border: 0;
                cursor: pointer;
                background: var(--paper);
                color: var(--print);
            }

            .toolbar a {
                background: transparent;
                color: var(--paper);
                border: 1px solid rgb(239 228 194 / 40%);
            }

            .hint {
                margin: 0;
                color: rgb(239 228 194 / 60%);
                font-size: 13px;
                text-align: center;
            }

            /* A5 landscape, scaled down on narrow screens. */
            .sheet {
                position: relative;
                width: 210mm;
                height: 148mm;
                max-width: 100%;
                aspect-ratio: 210 / 148;
                padding: 9mm 10mm 7mm 16mm;
                color: var(--print);
                background-color: var(--paper);
                background-image:
                    radial-gradient(ellipse at 15% 10%, rgb(255 255 255 / 45%), transparent 55%),
                    radial-gradient(ellipse at 90% 95%, rgb(140 100 40 / 18%), transparent 55%),
                    url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='160' height='160'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='3' stitchTiles='stitch'/%3E%3CfeColorMatrix values='0 0 0 0 0.45 0 0 0 0 0.35 0 0 0 0 0.2 0 0 0 0.09 0'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E");
                box-shadow:
                    inset 0 0 50px rgb(120 85 30 / 28%),
                    0 20px 50px rgb(0 0 0 / 55%);
                display: grid;
                grid-template-rows: auto auto 1fr auto;
                gap: 4mm;
                overflow: hidden;
            }

            /* Perforated tear-off edge. */
            .sheet::before {
                content: '';
                position: absolute;
                inset: 0 auto 0 8mm;
                width: 2mm;
                background: radial-gradient(circle, #2b2620 0.55mm, transparent 0.65mm) 0 0 / 2mm 4mm repeat-y;
                opacity: 0.8;
            }

            .sheet::after {
                content: '';
                position: absolute;
                inset: 0 auto 0 11.5mm;
                border-left: 0.3mm dashed rgb(179 54 44 / 45%);
            }

            header {
                display: grid;
                grid-template-columns: 1fr auto 1fr;
                align-items: start;
                gap: 4mm;
                border-bottom: 0.5mm solid var(--print);
                padding-bottom: 2.5mm;
            }

            .office {
                font-size: 8.5pt;
                line-height: 1.35;
                letter-spacing: 0.08em;
                text-transform: uppercase;
            }

            .office strong {
                display: block;
                font-size: 10pt;
            }

            h1 {
                margin: 0;
                font: 700 30pt/1 'Roboto Condensed', sans-serif;
                letter-spacing: 0.32em;
                padding-left: 0.32em;
            }

            .meta {
                justify-self: end;
                display: grid;
                grid-template-columns: auto 22mm;
                column-gap: 2mm;
                row-gap: 1mm;
                font-size: 8pt;
                text-transform: uppercase;
                letter-spacing: 0.05em;
            }

            .field {
                border-bottom: 0.25mm solid var(--print);
                min-height: 4.5mm;
            }

            .typed {
                font-family: 'Special Elite', 'Courier New', monospace;
                color: var(--ink);
                text-transform: uppercase;
                text-shadow: 0 0 0.4px rgb(35 38 46 / 70%);
                letter-spacing: 0.02em;
            }

            .meta .typed {
                font-size: 9.5pt;
                padding-left: 1mm;
            }

            .addresses {
                display: grid;
                grid-template-columns: auto 1fr auto 45mm;
                align-items: end;
                column-gap: 3mm;
                font-size: 8.5pt;
                text-transform: uppercase;
                letter-spacing: 0.06em;
            }

            .addresses .typed {
                font-size: 12pt;
                padding: 0 1mm 0.5mm;
            }

            .body {
                display: grid;
                grid-template-columns: 1fr 50mm;
                gap: 6mm;
                min-height: 0;
            }

            .label {
                font-size: 8pt;
                text-transform: uppercase;
                letter-spacing: 0.08em;
                margin-bottom: 1.5mm;
            }

            /* Ruled lines of the message field, with the text typed onto them. */
            .message {
                position: relative;
                height: calc(100% - 5.5mm);
                background: repeating-linear-gradient(to bottom, transparent 0 7.7mm, rgb(179 54 44 / 55%) 7.7mm 8mm);
            }

            .message .typed {
                margin: 0;
                padding: 2.2mm 1.5mm 0;
                font-size: 13.5pt;
                line-height: 8mm;
                word-spacing: 0.15em;
            }

            /* Login and password are case-sensitive, so they are typed as they are. */
            .message .as-is {
                text-transform: none;
            }

            .attachment {
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                gap: 1.5mm;
                border: 0.4mm solid var(--print);
                padding: 2.5mm;
                text-align: center;
            }

            .attachment svg {
                display: block;
                width: 40mm;
                height: 40mm;
            }

            .attachment .typed {
                font-size: 8.5pt;
            }

            footer {
                display: flex;
                justify-content: space-between;
                align-items: end;
                font-size: 7pt;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                opacity: 0.85;
            }

            .signature {
                display: flex;
                align-items: end;
                gap: 2mm;
            }

            .signature .field {
                width: 40mm;
            }

            /* The rubber stamp, pressed a little crooked over the message. */
            .stamp {
                position: absolute;
                left: 118mm;
                bottom: 11mm;
                width: 34mm;
                height: 34mm;
                transform: rotate(-14deg);
                color: var(--stamp);
                opacity: 0.78;
                mix-blend-mode: multiply;
                pointer-events: none;
            }

            @media print {
                body {
                    padding: 0;
                    background: none;
                    display: block;
                }

                .toolbar,
                .hint {
                    display: none;
                }

                .sheet {
                    box-shadow: inset 0 0 50px rgb(120 85 30 / 28%);
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }

                .sheet::before {
                    opacity: 0;
                }
            }
        </style>
    </head>
    <body>
        <div class="toolbar">
            <button type="button" onclick="window.print()">Drukuj / zapisz PDF</button>
            <a href="{{ url('/admin') }}">Wróć do panelu</a>
        </div>
        <p class="hint">Format A5 poziomo. W oknie drukowania włącz „Grafika tła”, żeby zachować kolor papieru.</p>
        @if (blank($password))
            <p class="hint" style="color: #f2b8b0;">Brak hasła gracza: ustaw je w panelu przy grze, żeby trafiło na telegram.</p>
        @endif

        <article class="sheet">
            <header>
                <div class="office">
                    <strong>Urząd telegraficzny</strong>
                    Fortis · okienko nr 62<br>
                    Druk Tg-1
                </div>

                <h1>TELEGRAM</h1>

                <div class="meta">
                    <span>Nr nadania</span><span class="field typed">0062</span>
                    <span>Słów</span><span class="field typed">{{ $words }}</span>
                    <span>Dnia</span><span class="field typed">{{ $date }}</span>
                    <span>Godz.</span><span class="field typed">{{ now()->format('H.i') }}</span>
                </div>
            </header>

            <div class="addresses">
                <span>Adresat</span><span class="field typed">OB. {{ $addressee }}</span>
                <span>Nadawca</span><span class="field typed">EKIPA FORTIS</span>
            </div>

            <div class="body">
                <div>
                    <div class="label">Treść</div>
                    <div class="message">
                        <p class="typed">
                            {{ $message }}<br>
                            LUB WEJDŹ NA {{ mb_strtoupper($site) }} STOP<br>
                            LOGIN: <span class="as-is">{{ $login }}</span><br>
                            @if (filled($password))
                                HASŁO: <span class="as-is">{{ $password }}</span>
                            @endif
                        </p>
                    </div>
                </div>

                <div class="attachment">
                    <div class="label" style="margin: 0;">Załącznik — pilne</div>
                    {!! $qr !!}
                    <span class="typed">Zeskanuj telefonem</span>
                </div>
            </div>

            <footer>
                <span>Za treść telegramu urząd nie odpowiada · Doręczyć do rąk własnych</span>
                <span class="signature">Podpis przyjmującego <span class="field"></span></span>
            </footer>

            <svg class="stamp" viewBox="0 0 100 100" aria-hidden="true">
                <defs>
                    <path id="stamp-ring" d="M50,50 m-36,0 a36,36 0 1,1 72,0 a36,36 0 1,1 -72,0" />
                </defs>
                <circle cx="50" cy="50" r="47" fill="none" stroke="currentColor" stroke-width="2.2" />
                <circle cx="50" cy="50" r="29" fill="none" stroke="currentColor" stroke-width="1.4" />
                <text font-family="Roboto Condensed, sans-serif" font-size="9.5" font-weight="700" letter-spacing="1.6" fill="currentColor">
                    <textPath href="#stamp-ring">URZĄD TELEGRAFICZNY ✶ FORTIS ✶</textPath>
                </text>
                <text x="50" y="47" text-anchor="middle" font-family="Special Elite, monospace" font-size="9" fill="currentColor">{{ $date }}</text>
                <text x="50" y="59" text-anchor="middle" font-family="Roboto Condensed, sans-serif" font-size="8" font-weight="700" fill="currentColor">62 LATA</text>
            </svg>
        </article>
    </body>
</html>
