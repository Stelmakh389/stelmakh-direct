#!/bin/bash
# Автодеплой: раз в пару минут забирает ветку main с GitHub и обновляет сайт.
# Не трогает api/config.php (токены) и api/data (заявки). Перед копированием проверяет PHP на ошибки.
# Ставится один раз (см. README), дальше работает по cron без паролей.
REPO="${DEPLOY_REPO:-https://github.com/Stelmakh389/stelmakh-direct}"
SITE="${DEPLOY_SITE:-$HOME/www/stelmakh-agency.ru}"
WORK="${DEPLOY_WORK:-$HOME/.deploy_src}"
REVF="$HOME/.deploy_rev"
LOG="$HOME/deploy.log"
PHP="$(command -v php || echo /usr/bin/php)"
log() { echo "$(date '+%F %T') $*" >> "$LOG"; }

exec 9>"$HOME/.deploy.lock"; flock -n 9 || exit 0
command -v git >/dev/null || { log "git не найден"; exit 1; }

if [ -d "$WORK/.git" ]; then
  cd "$WORK" || exit 1
  git fetch -q --depth 1 origin main 2>>"$LOG" || { log "fetch не удался"; exit 1; }
  NEW="$(git rev-parse FETCH_HEAD)"
  [ "$NEW" = "$(cat "$REVF" 2>/dev/null)" ] && exit 0
  git reset -q --hard FETCH_HEAD
else
  rm -rf "$WORK"
  git clone -q --depth 1 --branch main "$REPO" "$WORK" 2>>"$LOG" || { log "clone не удался"; exit 1; }
  cd "$WORK" || exit 1
  NEW="$(git rev-parse HEAD)"
fi

for f in $(find api -name '*.php'); do
  "$PHP" -l "$f" >/dev/null 2>&1 || { log "ошибка PHP в $f, деплой $NEW отменён"; exit 1; }
done

tar cf - --exclude=.git --exclude=.gitignore --exclude=README.md --exclude=deploy.sh --exclude=api/config.php --exclude=api/data . | (cd "$SITE" && tar xf -) \
  || { log "копирование не удалось"; exit 1; }
echo "$NEW" > "$REVF"
log "задеплоено ${NEW:0:8}"
