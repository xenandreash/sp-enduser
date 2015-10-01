Translate
=========

The enduser project supports translation of the web interface and daily digest messages. It's translation support is based on the Smarty template engine using gettext (.po) style language files.

Add a new language
------------------
In order to add a new language. Do the following:

```
php vendor/bin/tsmarty2c.php templates > messages.po
vim messages.po
mv messages locale/xx_XX/LC_MESSAGES/messages.po
```

(where xx_XX is the language name; eg. sv_SE)

Update a language file
----------------------

```
php vendor/bin/tsmarty2c.php templates > new.po
msgcat new.po locale/sv_SE/LC_MESSAGES/messages.po > new2.po
diff locale/sv_SE/LC_MESSAGES/messages.po new2.po
mv new2.po locale/sv_SE/LC_MESSAGES/messages.po
msgfmt -o locale/sv_SE/LC_MESSAGES/messages.mo locale/sv_SE/LC_MESSAGES/messages.po
```
