<!doctype html>
<html lang="ru">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Спасибо за заявку</title>
    <link rel="icon" href="/portfolio/site/liftx/assets/img/favicon.svg" type="image/svg+xml">
    <style>
      :root {
        --bg: #f4f4f4;
        --ink: #102129;
        --muted: #66747b;
        --accent: #ed3c3c;
        --surface: #ffffff;
      }

      * {
        box-sizing: border-box;
      }

      body {
        min-height: 100vh;
        margin: 0;
        display: grid;
        place-items: center;
        background:
          radial-gradient(circle at 12% 18%, rgba(237, 60, 60, 0.12), transparent 32%),
          linear-gradient(135deg, #ffffff 0%, var(--bg) 56%, #eeeeee 100%);
        color: var(--ink);
        font-family: "Montserrat", sans-serif;
      }

      .thanks {
        width: min(100%, 760px);
        padding: 24px;
      }

      .thanks__card {
        padding: clamp(30px, 7vw, 64px);
        border-radius: 28px;
        background: var(--surface);
        box-shadow: 0 24px 80px rgba(16, 33, 41, 0.12);
        text-align: center;
      }

      .thanks__icon {
        width: 72px;
        height: 72px;
        margin: 0 auto 24px;
        display: grid;
        place-items: center;
        border-radius: 22px;
        background: var(--accent);
        color: #ffffff;
        font-size: 38px;
        font-weight: 900;
      }

      h1 {
        margin: 0 0 16px;
        font-size: clamp(32px, 6vw, 56px);
        line-height: 1.08;
        font-weight: 900;
      }

      p {
        max-width: 560px;
        margin: 0 auto 30px;
        color: var(--muted);
        font-size: clamp(17px, 3vw, 21px);
        line-height: 1.55;
        font-weight: 600;
      }

      .thanks__actions {
        display: flex;
        justify-content: center;
        gap: 14px;
        flex-wrap: wrap;
      }

      a {
        display: inline-flex;
        min-height: 56px;
        align-items: center;
        justify-content: center;
        padding: 16px 26px;
        border-radius: 14px;
        background: var(--accent);
        color: #ffffff;
        font-size: 16px;
        font-weight: 800;
        text-decoration: none;
      }

      a:hover {
        filter: brightness(0.96);
      }

      .thanks__phone {
        background: transparent;
        color: var(--ink);
        border: 1px solid rgba(16, 33, 41, 0.16);
      }

      @media (max-width: 560px) {
        body {
          place-items: stretch;
        }

        .thanks {
          display: grid;
          min-height: 100vh;
          align-items: center;
          padding: 14px;
        }

        .thanks__card {
          border-radius: 22px;
        }

        .thanks__actions {
          display: grid;
        }
      }
    </style>
  
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="/portfolio/site/liftx/assets/css/fonts.css">
    <style>.demo-ribbon{position:relative;z-index:10000;display:flex;flex-wrap:wrap;align-items:center;justify-content:center;gap:4px 14px;padding:8px 16px;background:#050915;color:#EEF2FF;font:500 13px/1.4 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;text-align:center}.demo-ribbon b{color:#C6F25E;font-weight:600}.demo-ribbon a{color:#C6F25E;text-decoration:underline;text-underline-offset:2px}</style>
  </head>
  <body>
<div class="demo-ribbon"><span><b>Демо-копия</b> сайта из портфолио Андрея Стельмаха. Заявки отсюда не отправляются.</span><a href="/portfolio/liftx/">Разбор проекта →</a></div>
    <main class="thanks">
      <section class="thanks__card" aria-labelledby="thanks-title">
        <div class="thanks__icon" aria-hidden="true">✓</div>
        <h1 id="thanks-title">Спасибо, заявка отправлена</h1>
        <p>Менеджер свяжется с вами в ближайшее время, уточнит задачу и подберет подходящий подъемник.</p>
        <div class="thanks__actions">
          <a href="/portfolio/site/liftx/">Вернуться на сайт</a>
        </div>
      </section>
    </main>
  </body>
</html>
