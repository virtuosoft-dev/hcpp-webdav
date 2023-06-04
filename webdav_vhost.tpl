<VirtualHost *:{{port}}>
  ServerName webdav-{{username}}.{{domain}}.{{tld}}

  DocumentRoot /var/www/html

  <Directory /home/{{username}}/web>
    Options Indexes FollowSymLinks
    AllowOverride None
    Require all granted
  </Directory>

  <Location />
    DAV On
    AuthType Basic
    AuthName "WebDAV"
    AuthUserFile /home/{{username}}/webdav.password
    Require valid-user
    Options +Indexes

    # Enable compression
    SetOutputFilter DEFLATE

    # Compress specific MIME types
    AddOutputFilterByType DEFLATE text/plain
    AddOutputFilterByType DEFLATE text/html
    AddOutputFilterByType DEFLATE text/xml
    AddOutputFilterByType DEFLATE text/css
    AddOutputFilterByType DEFLATE application/xml
    AddOutputFilterByType DEFLATE application/xhtml+xml
    AddOutputFilterByType DEFLATE application/rss+xml
    AddOutputFilterByType DEFLATE application/javascript
    AddOutputFilterByType DEFLATE application/x-javascript
    AddOutputFilterByType DEFLATE application/json
    AddOutputFilterByType DEFLATE application/xml+rss

    # Common programming language file formats
    AddOutputFilterByType DEFLATE application/x-httpd-php-source .php
    AddOutputFilterByType DEFLATE application/x-go-source .go
    AddOutputFilterByType DEFLATE text/x-python .py
    AddOutputFilterByType DEFLATE text/x-csrc .c
    AddOutputFilterByType DEFLATE text/x-rustsrc .rs

    # Markup and other text file formats
    AddOutputFilterByType DEFLATE text/x-markdown .md
    AddOutputFilterByType DEFLATE text/x-latex .tex
    AddOutputFilterByType DEFLATE text/x-shellscript .sh
    AddOutputFilterByType DEFLATE text/x-perl .pl
  </Location>

  User {{username}}
  Group {{username}}
</VirtualHost>
