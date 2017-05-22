=== Eighty / 20 Results: Member Cancellation Policy for Paid Memberships Pro ===
Contributors: sjolshagen
Tags: pmpro, paid memberships pro, members, memberships, membership cancellation choice
Requires at least: 4.7
Tested up to: 4.7.5
Stable tag: 1.3

Adds a membership level setting to configure how PMPro will handle member cancellations.

== Description ==

By default, PMPro terminates the user's access to protected content immediately. This plugin will configure the membership to end at the end
of the currently paid-for membership period by default. However, it's possible to change this default to "immediately"
on a per-membership level basis via the Membership Level settings.

== Installation ==

1. Upload the `e20r-member-cancellation-policy` folder to the `/wp-content/plugins/` directory.
1. Activate the plugin through the 'Plugins' menu in WordPress.
1. Edit your membership levels and set the Membership Cancellation Policy option for each level.

== Changelog == 

== 1.3 ==

* ENHANCEMENT/FIX: Change the Account 'Expiration' header to 'Ends' if the policy is 'end of period' and the user's membership has an enddate

== 1.2 ==

* ENHANCEMENT/FIX: Only load policy engine if user is logged in
* ENHANCEMENT/FIX: Load the pmpro action hooks if the policy is set to end of period for that user's level
* BUG/FIX: Would incorrectly exit for recurring memberships

== 1.1 ==

* ENHANCEMENT/FIX: Handle class instantiation

== 1.0 ==

* BUG/FIX: Incorrect build process for change logs
* BUG/FIX: Build environment tweaks
* ENHANCEMENT/FIX: Add license header & fix css elements for add-on
* ENHANCEMENT/FIX: Add styling for the level settings page
* ENHANCEMENT/FIX: Use correct setting name for termination policy choice
* ENHANCEMENT/FIX: Set the default value for the current level while saving
* Initial Commit

