# Outlawz CLI

Maakt nieuwe projecten van Outlawz aan en zet projecten klaar voor [Outlawz Cloud](https://outlawz.cloud): ze worden
gebouwd op de golden images van [cloud-images](https://github.com/outlawz-team/cloud-images).

## Installeren

```bash
composer global require outlawz-team/cli
```

Zorg dat de bin-map van composer in je `PATH` staat (`composer global config bin-dir --absolute`). Bijwerken:

```bash
outlawz self-update
```

of `composer global update outlawz-team/cli`.

## Wat je nodig hebt

Elk commando controleert vooraf wat het nodig heeft en zegt hoe je het installeert:

| Gereedschap | Voor |
|---|---|
| `git` en `git flow` (`brew install git-flow`) | alle projecten: `master` en `develop` |
| `composer`, `php` en `npm` (Herd) | alle projecten |
| `wp` (`brew install wp-cli`) en MySQL (DBngin) | `create:radicle` |
| `gh` (`brew install gh && gh auth login`) | alleen als het commando de repository op GitHub aanmaakt |

## Commando's

### `outlawz create:radicle <map>`

Een nieuwe WordPress-site op [Radicle](https://roots.io/radicle/): vraagt de naam, de database, de URL en de plugins, en
zet daarna alles klaar:

- Radicle met een eigen git-geschiedenis, `master` en `develop` (git flow);
- de gekozen plugins, de pakketten van Outlawz, prettier, lint-staged en husky;
- `.env` met de database en echte keys en salts;
- de bestanden van Outlawz (thema, layouts, blocks, post types);
- WordPress geïnstalleerd, in het Nederlands, met een pagina Home (login `outlawz` / `Welkom01!`);
- klaar voor Outlawz Cloud (zie hieronder);
- een eerste commit op `develop`, en als je dat wilt de repository `outlawz-team/<map>` op GitHub, met het secret
  `COMPOSER_AUTH` (bij ACF Pro) en de eerste push. Die push bouwt meteen het image voor test.

Gaat een stap mis, dan stopt het commando en zegt het welke stap en waarom. Haal dan de map en de database weg en
probeer het opnieuw.

### `outlawz create:platform <map>`

Een nieuw platform (Laravel-API en Next.js-dashboard) vanuit
[platform-boilerplate](https://github.com/outlawz-team/platform-boilerplate):

- de boilerplate met een eigen git-geschiedenis, `master` en `develop`;
- de namen uit de checklist van de boilerplate (`APP_NAME`, het composer-pakket, het npm-pakket, de Bruno-collectie);
- klaar voor Outlawz Cloud: `image.yml` op het golden image van het platform, `php` op `^8.4` (het platform-image
  heeft alleen 8.4) en `output: "standalone"` in `next.config`;
- de API geïnstalleerd (`composer run setup` en de gebruiker uit de seeder) en het dashboard (`npm install`,
  `.env.local`);
- een eerste commit op `develop`, en als je dat wilt de repository op GitHub met de eerste push.

### `outlawz cloud:init [map]`

Zet een bestaand project over op het golden image: Radicle, Bedrock in `public/content`, of een platform met
`apps/api` en `apps/dashboard`. Zonder map de huidige map. Het vraagt vóór elke wijziging of het mag; `--yes`
doet alles zonder te vragen.

- haalt de eigen `Dockerfile` en `docker/` weg (de oude opzet); instellingen van de site daarin horen in `cloud/`;
- zet `.github/workflows/image.yml` op het golden image en haalt de voorbeeld-workflows van Roots weg;
- zet een PHP-eis in `composer.json` die het golden image accepteert, als die er niet is;
- WordPress: de blokken voor `DB_SSL` en WP Mail SMTP in de configuratie, en `cloud/config.yml` voor een site die
  met bud bouwt;
- platform: `output: "standalone"` in `next.config` van het dashboard;
- private pakketten (ACF Pro, private repo's): zet het secret `COMPOSER_AUTH` vanuit je `auth.json`, als het er nog niet is.

Wat het niet zelf kan, staat aan het eind. Daarna committen en naar `develop` pushen.

## Outlawz Cloud

Een project heeft geen eigen Dockerfile: `.github/workflows/image.yml` roept de workflow van cloud-images aan, die het
image bouwt op het golden image (`wordpress/v1` of `platform/v1`, in `config/cloud.php`). Uitzonderingen per project
staan in `cloud/` van het project; zie de README van cloud-images. Het project zelf koppel je daarna in Outlawz Cloud.

## Ontwikkelen

```bash
composer install
php outlawz list
vendor/bin/pest
```

De commando's staan in `app/Commands`, de bestanden die ze neerzetten in `stubs/`, de plugins in
`config/wordpress.php` en de golden images in `config/cloud.php`.

## Release

Vanaf `develop`, zonder openstaande wijzigingen:

```bash
scripts/release.sh 1.2.0
```

Het script doet de release met git flow, bouwt `builds/outlawz` met die versie, tagt hem en pusht `master`, `develop`
en de tag. `composer global require` en `self-update` nemen de phar uit de tag, dus hij moet in de release gebouwd
zijn. De workflow Release controleert dat en maakt de release op GitHub aan.
