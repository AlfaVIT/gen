# Проект «gen» — сайт «Семейное древо»

Публичный адрес сайта: **https://gen.opensite.su/** (HTTP 200, без редиректов)
Хостинг: Jino (myjino), сервер `srv69-h-st.jino.ru` (195.161.62.167)

## Инфраструктура

| | |
|---|---|
| Git | `https://github.com/AlfaVIT/gen.git`, основная ветка `main` |
| FTP | `opensite.myjino.ru:21`, логин `opensite_gen`, пароль — в `ftp.txt` (в git не попадает) |
| FTPS/SFTP | порт `2222` |
| Стек | PHP 7.4 (alt-php, см. `.envrc`), SQLite (`storage/app.sqlite`), без фреймворка |

## Деплой

Только через `deploy.ps1` (заливает по FTP лишь изменённое, список файлов берёт из git):

```powershell
.\deploy.ps1 -DryRun    # посмотреть, что уедет
.\deploy.ps1            # залить изменения с последнего деплоя до HEAD
.\deploy.ps1 -All       # полный деплой
.\deploy.ps1 -Verify    # сверить размеры файлов с FTP
.\deploy.ps1 -Delete    # + удалять на FTP файлы, удалённые в git
```

Состояние последнего деплоя — в `.deploy_state` (локальный, в git не хранится).
Список того, что не заливается на хостинг, — в `.deployignore`.

## Важное

- **Не коммитить и не заливать**: `ftp.txt`, `storage/app.sqlite`, `uploads/*` (пользовательские файлы). Это уже задано в `.gitignore` и серверном `.gitignore`.
- Текстовые файлы — LF (`.gitattributes` `* text=auto eol=lf`), хостинг Linux.
- Структура: `index.php` (точка входа) → `app/bootstrap.php`, `app/config.php`, `app/inc/*` (auth, db, genealogy, media, upload, mailer, xml), `app/pages_*.php` (контроллеры), `app/views/*` (шаблоны), статика в `assets/`.
- После правок в `app/` проверять работоспособность на https://gen.opensite.su/ (админка и загрузка медиа — основные уязвимые места).
