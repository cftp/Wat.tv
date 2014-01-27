=== Embed Wat.TV Videos ===
Contributors: TJNowell, codeforthepeople
Tags: video, embed, oembed, wat.tv
Requires at least: 3.7
Tested up to: 3.8
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily embed wat.tv videos by pasting the URL into your WordPress post.

== Description ==
[wat.tv](http://www.wat.tv/aproposwat) is a video hosting and streaming website, owned by France’s [TF1](http://www.tf1.fr/) television channel.

This plugin adds [OEmbed-style embedding](http://codex.wordpress.org/Embeds) of wat.tv videos. 

Paste the URL of a video’s wat.tv page on a blank line by itself, save, and an embed will appear in its place on the front end. No need for any HTML code: it just works.

(The technical bit: wat.tv has an undocumented, and relatively basic oEmbed service; but we do not use it. We collect all the data to show the video from the web page’s `<head>` metadata.)

Wat.tv est une plateforme française de vidéos en ligne qui fait partie du groupe TF1.

Cette extension vous permet d'intégrer une vidéo wat.tv directement dans votre article en émulant le protocole oEmbed.

Copiez l'adresse de la page de la vidéo que vous voulez intégrer, et collez-la dans votre article. (Laissez l'adresse seule sur la ligne.) Et c'est tout ! Quand vous afficherez l'article sur le site, c'est le lecteur vidéo qui apparaîtra. Vous n'avez plus besoin d'utiliser le code du lecteur embarqué.

== Installation ==
1. Upload to the "/wp-content/plugins/" directory.
1. Activate the plugin through the "Plugins" menu in WordPress.
1. Paste the URL of video’s wat.tv page on post content on a blank line and save

== Screenshots ==

1. An embedded video
2. The editor page for an embedded video

== Frequently Asked Questions ==
= Does this handle https? =
Yes, it handles both http and https urls.

= I activated the plugin, but I can’t see a settings screen? =
Don’t worry, there isn’t one. It just works.

= I pasted a link but no video embed appears? =
You must paste the URL on it’s own line, and it must not be a link. The editor will sometimes auto-link the URL, and if this happens, you must unlink the URL using the editor toolbar.

= Why is my embedded video not working? =
wat.tv users can choose to prevent embedding of their uploaded video on other sites, due to broadcast restrictions or personal choice. In this case, you will normally see a blank or inactive player window.

= Can I use the embed shortcode? =
Yes, [embed] shortcodes will also work

== Changelog ==
= 1.1 =
* Local OEmbed Provider and endpoint added.
* Thumbnail image and title data added to endpoint

= 1.0 =
* Initial release.

== Upgrade Notice ==
= 1.1 =
* Local OEmbed Provider and endpoint added.
* Thumbnail image and title data added to endpoint

= 1.0 =
* Initial release.

