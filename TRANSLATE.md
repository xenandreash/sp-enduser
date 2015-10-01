Translate
=========

The enduser project supports translation of the web interface and daily digest messages. Its translation support is based on the Smarty template engine using gettext (.po) style language files.

Adding a new language
---------------------

```
php vendor/bin/tsmarty2c.php templates > messages.po
vim messages.po
msgfmt -o locale/xx_XX/LC_MESSAGES/messages.mo messages.po
```

(where xx_XX is the language name; eg. sv_SE)

Update a language file
----------------------

```
php vendor/bin/tsmarty2c.php templates > all.po
msgcat all.po locale/sv_SE/LC_MESSAGES/messages.po > new.po
diff locale/sv_SE/LC_MESSAGES/messages.po new.po
mv new.po locale/sv_SE/LC_MESSAGES/messages.po
msgfmt -o locale/sv_SE/LC_MESSAGES/messages.mo locale/sv_SE/LC_MESSAGES/messages.po
```
