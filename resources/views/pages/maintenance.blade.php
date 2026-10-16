<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#005136">
    <title>Portal Closed | COOU SIWES</title>
    <style>
        :root {
            --brand-50: #effcf5;
            --brand-100: #d9f7e7;
            --brand-300: #7edcad;
            --brand-400: #3fc27c;
            --brand-500: #139b5c;
            --brand-600: #005136;
            --brand-700: #00452e;
            --ink: #10231c;
            --gold: #d99b00;
            --gold-soft: #f5c84b;
        }

        * { box-sizing: border-box; }

        body {
            min-height: 100vh;
            margin: 0;
            overflow: hidden;
            color: var(--ink);
            background:
                radial-gradient(circle at 12% 18%, rgba(63, 194, 124, .2), transparent 25rem),
                radial-gradient(circle at 90% 80%, rgba(217, 155, 0, .13), transparent 24rem),
                linear-gradient(135deg, #f8fffb 0%, var(--brand-50) 48%, #fffdf6 100%);
            font-family: 'Instrument Sans', ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        .maintenance-page {
            position: relative;
            display: grid;
            min-height: 100vh;
            place-items: center;
            isolation: isolate;
            padding: 2rem 1.25rem;
        }

        .maintenance-page::before,
        .maintenance-page::after {
            position: absolute;
            z-index: -1;
            width: 12px;
            height: 12px;
            content: '';
            border-radius: 50%;
            background: var(--brand-400);
            box-shadow: 22vw 11vh 0 rgba(0, 81, 54, .25), 64vw 18vh 0 rgba(217, 155, 0, .55), 82vw 72vh 0 rgba(63, 194, 124, .4), 18vw 82vh 0 rgba(217, 155, 0, .35);
            animation: float-particles 8s ease-in-out infinite alternate;
        }

        .maintenance-page::before { top: 12%; left: 8%; }
        .maintenance-page::after { right: 12%; bottom: 12%; animation-delay: -3s; }

        .maintenance-shell {
            display: grid;
            width: min(100%, 72rem);
            min-height: min(39rem, calc(100vh - 4rem));
            overflow: hidden;
            border: 1px solid rgba(0, 81, 54, .16);
            border-radius: 2rem;
            background: rgba(255, 255, 255, .72);
            box-shadow: 0 2rem 5rem rgba(0, 81, 54, .15), 0 0 0 8px rgba(255, 255, 255, .38);
        }

        .maintenance-copy {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: clamp(2rem, 6vw, 5.5rem);
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: .55rem;
            width: fit-content;
            padding: .55rem .85rem;
            border: 1px solid rgba(0, 81, 54, .18);
            border-radius: 999px;
            color: var(--brand-700);
            background: rgba(217, 247, 231, .75);
            font-size: .75rem;
            font-weight: 800;
            letter-spacing: .12em;
            text-transform: uppercase;
        }

        .eyebrow-dot {
            width: .55rem;
            height: .55rem;
            border-radius: 50%;
            background: var(--gold);
            box-shadow: 0 0 .8rem rgba(217, 155, 0, .8);
            animation: pulse 1.8s ease-in-out infinite;
        }

        h1 {
            max-width: 34rem;
            margin: 1.5rem 0 1rem;
            color: var(--brand-700);
            font-size: clamp(1.45rem, 6vw, 5.5rem);
            font-weight: 400;
            letter-spacing: 0;
            line-height: .98;
        }

        h1 span { color: var(--gold); }

        .maintenance-copy p {
            max-width: 31rem;
            margin: 0;
            color: rgba(16, 35, 28, .68);
            font-size: clamp(1rem, 1.8vw, 1.2rem);
            line-height: 1.7;
        }

        .brand-line {
            width: 6rem;
            height: .3rem;
            margin-top: 2rem;
            border-radius: 999px;
            background: linear-gradient(90deg, var(--brand-600), var(--brand-400), var(--gold));
        }

        .door-stage {
            position: relative;
            display: grid;
            min-height: 22rem;
            place-items: center;
            overflow: hidden;
            background: linear-gradient(145deg, var(--brand-700), var(--brand-600) 55%, #002f20);
        }

        .door-stage::before {
            position: absolute;
            inset: 0;
            content: '';
            opacity: .28;
            background-image: linear-gradient(135deg, transparent 48%, rgba(126, 220, 173, .3) 49%, transparent 51%);
            background-size: 4rem 4rem;
            animation: drift 16s linear infinite;
        }

        .door-glow {
            position: absolute;
            width: 18rem;
            height: 18rem;
            border-radius: 50%;
            background: rgba(63, 194, 124, .18);
            filter: blur(2rem);
            animation: glow 4s ease-in-out infinite;
        }

        .door-frame {
            position: relative;
            z-index: 1;
            width: min(62%, 18rem);
            aspect-ratio: .68;
            padding: .7rem;
            border: .35rem solid var(--gold);
            border-bottom-width: .7rem;
            border-radius: .9rem .9rem .25rem .25rem;
            background: rgba(217, 155, 0, .14);
            box-shadow: 0 1.8rem 3rem rgba(0, 0, 0, .24), 0 0 2rem rgba(245, 200, 75, .25);
        }

        .door {
            position: relative;
            width: 100%;
            height: 100%;
            overflow: hidden;
            transform-origin: left center;
            border: .18rem solid rgba(126, 220, 173, .7);
            border-radius: .35rem;
            background: linear-gradient(115deg, #0b7150, #005136 65%, #003a27);
            box-shadow: inset .8rem 0 1.5rem rgba(0, 0, 0, .16);
            animation: door-breathe 5s ease-in-out infinite;
        }

        .door::before,
        .door::after {
            position: absolute;
            left: 12%;
            right: 12%;
            height: 26%;
            content: '';
            border: 2px solid rgba(126, 220, 173, .45);
            border-radius: .2rem;
        }

        .door::before { top: 12%; }
        .door::after { bottom: 12%; }

        .door-knob {
            position: absolute;
            top: 50%;
            right: 12%;
            width: 1rem;
            height: 1rem;
            border: .2rem solid var(--gold-soft);
            border-radius: 50%;
            box-shadow: 0 0 1rem rgba(245, 200, 75, .8);
            animation: knob-pulse 2.5s ease-in-out infinite;
        }

        .door-lock {
            position: absolute;
            right: 10%;
            bottom: 9%;
            width: .7rem;
            height: .85rem;
            border-radius: .15rem;
            background: var(--gold-soft);
        }

        .door-lock::before {
            position: absolute;
            top: -.55rem;
            left: .1rem;
            width: .5rem;
            height: .7rem;
            content: '';
            border: .16rem solid var(--gold-soft);
            border-bottom: 0;
            border-radius: .5rem .5rem 0 0;
        }

        .door-caption {
            position: absolute;
            right: 1.25rem;
            bottom: 1.25rem;
            z-index: 2;
            color: rgba(255, 255, 255, .72);
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        @keyframes door-breathe { 0%, 100% { transform: perspective(900px) rotateY(0deg); } 50% { transform: perspective(900px) rotateY(-2deg); } }
        @keyframes knob-pulse { 0%, 100% { transform: scale(1); } 50% { transform: scale(1.22); } }
        @keyframes pulse { 0%, 100% { opacity: .55; transform: scale(.8); } 50% { opacity: 1; transform: scale(1.15); } }
        @keyframes glow { 0%, 100% { transform: scale(.92); opacity: .65; } 50% { transform: scale(1.12); opacity: 1; } }
        @keyframes drift { to { background-position: 4rem 4rem; } }
        @keyframes float-particles { to { transform: translateY(-1rem) translateX(.8rem); } }

        @media (min-width: 800px) {
            .maintenance-shell { grid-template-columns: 1.08fr .92fr; }
            .door-stage { min-height: auto; }
        }

        @media (max-width: 799px) {
            .maintenance-page { padding: 1rem; }
            .maintenance-shell { min-height: calc(100vh - 2rem); border-radius: 1.35rem; }
            .maintenance-copy { padding: 2.4rem 1.65rem 1.5rem; }
            .door-stage { min-height: 19rem; }
            h1 { margin-top: 1.15rem; font-size: clamp(1.5rem, 14vw, 4.5rem); }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation-duration: .01ms !important; animation-iteration-count: 1 !important; scroll-behavior: auto !important; }
        }
    </style>
</head>
<body>
    <main class="maintenance-page" aria-labelledby="maintenance-title">
        <section class="maintenance-shell">
            <div class="maintenance-copy">
                <div class="eyebrow"><span class="eyebrow-dot" aria-hidden="true"></span> COOU SIWES Portal</div>
                <h1 id="maintenance-title">The portal is <span>Closed</span> Till further Notice</h1>
                <!-- <p>We are carrying out important updates to improve your portal experience. Please check back later.</p> -->
                <div class="brand-line" aria-hidden="true"></div>
            </div>
            
        </section>
    </main>
</body>
</html>
