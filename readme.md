# KuzuhaScriptPHP+ (くずはすくりぷとPHP+)
An improved version of the PHP port of KuzuhaScript (くずはすくりぷと).
As of 2024/10/16, it only works with PHP8+
Last legacy PHP (from 4.1.0 to 7.4) version can be found here: [https://github.com/Heyuri/ksphp-plus/releases/tag/20240710](https://github.com/Heyuri/ksphp-plus/releases/tag/20240710)

[https://hiru.coresv.com/ksphp-plus/](https://hiru.coresv.com/ksphp-plus/)


This program is based on the 2005/04/01 modified version of KuzuhaScriptPHP (くずはすくりぷとPHP) by cion (しおん).

This program has originally been translated to English by [Anonymous-san at Strange World@Heyuri.net](https://dis.heyuri.net/bbs.php?c=08&m=tree&ff=202205.dat&s=3555) and several anonymous developers from Heyuri have contributed to it since.


* [KuzuhaScriptPHP (mirror)](http://qptn.x.fc2.com/up/dauso0059.zip)  
* [2005/04/01 modified version](http://qptn.x.fc2.com/up/dauso0073.zip)

## Maintainer information
### ヶ
* [https://hiru.coresv.com/](https://hiru.coresv.com/)
* [mthiru@protonmail.com](mailto:mthiru@protonmail.com)

### ＠Links
* [https://prev.strangeworld.icu/](https://prev.strangeworld.icu/)
* [linksh@outlook.jp](mailto:linksh@outlook.jp)

## Installation process (for reference only)
1. Unzip the downloaded ZIP file
2. Open and configure conf.php
3. Upload the files to the server using an FTP client or similar (it's a good idea to create a dedicated directory so that it doesn't get mixed up with other files)
4. Set the permissions as described in readme.md
5. Open a web browser, access bbs.php, and set the administrator password
6. Open your local conf.php file, paste the admin password generated in step 6 to 'ADMINPOST' => 'here' on line 36, then upload the file using your FTP client to overwrite it
7. Open your web browser, go to bbs.php, and see if you can post
8. Access the URL where the log files (bbs.log, files inside log/, etc.) are located using a web browser, and check if you can see it (if you can see it, please hide it with .htaccess, etc.)

## Recommended permission settings
Incorrect permissions can cause problems and data leaks (such as a post's IP address or remote host), so please make sure that they are set correctly.

```
[File structure]
|-- bbs.cnt   600 (writable)      Participant list record file (empty text file)
|-- bbs.log   600 (writable)      Log file (empty text file)
|-- conf.php  644 (read-only)     For configuration
|-- bbs.php 644 (read-only)     Main bulletin board script
|-- readme.md                     Instructions (this file)
|
|-- vanish.js                     Script for word filtering
|
|
+-- archive/  700 (writable)      ZIP archive storage directory
+-- count/    700 (writable)      Counter output directory
+-- log/      700 (writable)      Message log files (raw logs) storage directory
+-- sub/      755 (read-only)     Submodule storage directory
    |
    |-- bbsadmin.php    644 (read-only)     Administration module
    |-- bbslog.php      644 (read-only)     Log viewer module
    |-- bbstree.php     644 (read-only)     Tree view module
    |-- phpzip.inc.php  644 (read-only)     ZIP file creation library
```

If PHP runs as an Apache module, bbs.php will run as read-only, 
but if it runs as CGI, bbs.php needs to be set to 755 (executable).

## Memo:
### List of bbs.php?m=* meanings
m=g     Message log search
m=ad    Administrator mode
m=tree  Tree view
m=p     Post/reload
m=c     Settings
m=f     Follow screen
m=t     Thread display
m=s     Search by user
m=u     Execute UNDO

## History
### Cion (しおん) version
* 2003/01/21 work began
* 2003/01/31 0.0.1alpha
* 2003/02/03 0.0.2alpha
* 2003/02/11 0.0.3alpha
* 2003/02/13 0.0.4alpha
* 2003/02/14 0.0.5alpha
* 2003/02/16 0.0.6alpha
* 2003/02/18 0.0.7alpha

### Unofficial
* 2005/04/01 0.0.8alpha(Unofficial) A modified version released by a volunteer (Mirror: http://www.freak.ne.jp/~lunatica/home/up/freak/dauso0073.zip)

### Unknown dates (Hirugatake (蛭ヶ岳) version)
* Fixed UI, easier to use on smartphones etc.
* Switched to UTF-8 (＠Links)
* Update PHPZip to v1.2
* Various other fixes (not recorded)

### Unknown dates
* Slight change in coding style
* Fixed bug in follow-up posts
* Removed jcode-LE
* Fixed problem where user settings weren't reflected(?)
* Templates are no longer a concern
* Solved mysterious implementation of the func class (incomplete)
* Preperation for PHP7.x support

### 2018/10/12
* Changed name to "KuzuhaScriptPHP+"(くずはすくりぷとPHP+)
* Missing form data invalidated due to faulty checking
* Minor UI changes

### 2018/11/18
* Applied Gikoneko(擬古猫)'s tree view corrections
* Built-in vanish.js

### 2019/11/02
* Removed EZweb view (HDML)
* Removed imode view

### 2019/11/02
* Removed EZweb view (HDML)
* Removed imode view

### 2020/02/11
* Applied Gikoneko(擬古猫)'s tree view bugfixes

### 2020/03/15
* Seperated counters with commas

### 2020/03/29
* Added Gikoneko(擬古猫)'s YouTube embedding function

### 2021/03/08
* Design changes (text boxes, etc.)
* conf.php (changed expressions and default values)

### 2021/07/03
* Added Gikoneko(擬古猫)'s 2chtrip(20210625)
* Removed bbs.cgi

### 2021/07/27
* Fixed an issue where the admin password leaked when using both admin password and trip (Gikoneko(擬古猫))
* Maximum number of characters for Name, Email, and Title can now be set
* Moved description in bbs.php to readme.md

### 2022/05/06
* Moved bbs.php to index.php
* Minor UI fixes

### 20221127
* sub/patTemplate.php: Applied Gikoneko(擬古猫)'s fixes

### 20230118
* Fixed a bug where the tree was not displayed correctly

### 20230520
* Applied Gikoneko's admin path disclosure prevention (20210923)
* Applied Gikoneko's fixed handle name password prevention (20210923)

### Unknown dates (Heyuri version)
* Wholly translated to English
* Added kaomoji buttons
* Changed line height to 1
* Commented out the CSS for browsers to break lines
* Added a javascript for making it easy for users to break lines
* Commented out Gikoneko(擬古猫)'s YouTube embedding function
* Implemented a javascript for YouTube embedding
* Added Javascript for thumbnailing images, by default it's set to only work for Uploader@Heyuri

### 2024/10/16
* Migrated to PHP8
* Renamed index.php back to bbs.php
* Moved JS files to a separate directory

### 2025/04/29
* Fixed redirect not working after applying user settings
* Removed unused configs for customization off conf.php

### 2025/06/07
* Added support for IPv6 IPs
* Changed http header settings to allow embedding from anywhere

### 2025/09/09
* Fixed host bans

### 2025/11/08
* Added vertical scroller CSS for long lines (PC-only)
* Modified Kaomoji buttons to a fieldset
* Fixed a bug where clicking User Settings submitted the post if the form wasn't empty
* Made the ayashii breaker warn the user by glowing for long line posts

### 2025/11/09
* Added complete internationalization for Japanese, English being now optional
* Renamed software back to KuzuhaScriptPHP+ from KuzuhaScriptPHP+EN

### 2026/03/10
* Administrator keys can now be combined with a tripcode, implemented by gikonekos(擬古猫)

### 2026/03/15
* The way to enter administration menu is changed to a link at top
* Administrator menu is reworked, it was also changed to use sessions for security and convenience

## ToDo:
* View posts by thread
* Improve speed of unread on tree view
* Setting for whether or not to use the mobile module
* Form does not appear on the new post screen
* Proper use of multi-byte functions and jcode
* Setting for UNDO expiration date
* Have uploader thumbnailing javascript to easily support other instances of Uploader softwares

## Known Bugs:
* Large number of \&nbsp; appearances when searching logs
* When deleting your own post, sometimes you get a "this post could not be found" error instead of "deletion complete"



