=== Wordpress VBX Phone System (Lite) ===
Contributors: roblesterjr
Tags: phone, twilio, utilities, general, vbx, professional, calls, voicemail, messages, sms, text, text message
Requires at least: 4.6
Tested up to: 4.7
Stable tag: trunk
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Wordpress VBX - Powered by Twilio. Provides an internet based phone system managed right from your wordpress site.

== Description ==

Wordpress VBX with Twilio(c) lets you create a professional phone system for your users or subscribers to connect with. 

Admins are able to manage phone numbers, and create call flows for voice and messaging that will dial numbers by user, group, or specific number. The admin can set greetings, and program user based voicemail as well.

The plugin is extendable for developers as well! Developers can create plugins that interact with WP VBX core functions and also build applets to be used in vbx flows.

**NOTE: You need a Twilio Account for this plugin to work. Charges will apply.**

= Lite Features: =
* Dial
* Voicemail
* Greeting
* Sms static Reply
* Menu Prompt

= PRO Features: =
* All Lite Features +
* Sms Inbox + reply
* Transfer
* Conference
* Timed attendant
* Hold Queue
* Conditional Staff Flow
* Call in voicemail
* Language Prompt

== Installation ==

1. Upload the zip to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Settings->Plugin Name screen to configure the plugin
1. (Make your instructions match the desired user flow for activating and installing your plugin. Include any steps that might be needed for explanatory purposes)

== Screenshots ==

1. This is where a flow starts. Drag an applet in to direct the call to do something.
2. The Greeting applet lets you read text or play a recording to the caller. There is a drop zone for an applet to be executed after the greeting.
3. Enter your Twilio account SID and secret Key.
4. The menus.

== Changelog ==

=== Version 1.1 ===
Added get started page and fixed error in number search.

=== Version 1.0 ===
Added error control to activation for when credentials don't exist.