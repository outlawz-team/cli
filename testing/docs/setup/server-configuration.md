# Server Configuration

The document root for your site must be set to the `/public` directory of your Radicle project.

## Nginx configuration

Here's a basic Nginx configuration for Radicle:

```nginx
server {
  listen 80;
  server_name example.com;

  root /srv/www/example.com/public;
  index index.php index.htm index.html;

  # Prevent PHP scripts from being executed inside the uploads folder.
  location ~* /content/uploads/.*.php$ {
    deny all;
  }

  location / {
    try_files $uri $uri/ /index.php?$args;
  }
}
```

## Apache configuration

For Apache servers, ensure your virtual host points to the `/public` directory:

```apache
<VirtualHost *:80>
  DocumentRoot /var/www/html/radicle/public
  DirectoryIndex index.php index.html index.htm

  <Directory /var/www/html/radicle/public>
    Options -Indexes

    # .htaccess isn't required if you include this
    <IfModule mod_rewrite.c>
      RewriteEngine On
      RewriteBase /
      RewriteRule ^index.php$ - [L]
      RewriteCond %{REQUEST_FILENAME} !-f
      RewriteCond %{REQUEST_FILENAME} !-d
      RewriteRule . /index.php [L]
    </IfModule>
  </Directory>
</VirtualHost>
```

### `.htaccess` configuration

Radicle includes a `.htaccess` file in the `/public` directory with the following security and rewrite rules:

```apache
# BEGIN WordPress
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /
RewriteRule ^index.php$ - [L]
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . /index.php [L]
</IfModule>
# END WordPress
```
