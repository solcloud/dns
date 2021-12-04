# Dns over HTTPS (DOH)

Currently, implements _Google_ and _CloudFlare_ doh

## Files
- `www/doh-cli.php` for one time cli answers
- `www/resolve.php` for server - by default listening on _udp://127.0.0.1:5353_

## Notes

This project use low level parsing classes from https://github.com/yswery/PHP-DNS-SERVER/ (_lib/_ folder)
