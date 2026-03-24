# Local development

Radicle comes with configuration for two local development setups out of the box:

* [Lando](https://lando.dev/)
* [Trellis](https://roots.io/trellis/)

[Laravel Valet](https://laravel.com/docs/10.x/valet) also supports Radicle sites with no config required.

These tools aren't a requirement to use Radicle. Any local development tool can be used with as long as you set your document root to the `public` directory. You will also need to:

1. Run `npm install && npm run build`
1. Run `composer install`
1. Copy `.env.example` to `.env` and update the [environment variables](https://roots.io/bedrock/docs/installation/#getting-started)

## Lando

To use Lando with Radicle:

1. Run `npm install && npm run build`
1. Run `lando start`
1. Visit `https://radicle.lndo.site/`
1. Run `lando dev` to start the Vite dev server

You can run `lando login` to generate a passwordless wp-admin login URL (WordPress must first be installed).

### Vite configuration for Lando

If you need to configure Vite for Lando development, open `vite.config.js` and add the following to the top of `defineConfig`:

```javascript
server: {
  host: "0.0.0.0",
  port: 5173,
  strictPort: true,
  cors: {
    origin: "https://radicle.lndo.site",
    credentials: true,
  },
  hmr: {
    host: "localhost",
    protocol: "ws",
  },
},
```

## Trellis

Run `php .radicle-setup/trellis.php` to setup Trellis and apply the necessary modifications for Radicle. After Trellis has been setup you can start the VM:

```shell
$ cd trellis/
$ trellis vm start
```

Run `npm install && npm run build` before visiting your site at `http://example.test/`.

You can remove the `.radicle-setup/` directory after you've ran the Trellis script, or if you aren't planning to use Trellis.

Make sure to commit the changes to the repo as there will now be a `trellis/` folder.
