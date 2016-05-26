User Profiles for Bolt
======================

This [bolt.cm](https://bolt.cm/) extension lets you add new fields to the default Bolt users.
You can also create public profiles for your users.
It's pretty useful when your application depends on some records in a resource contenttype.

### Installation
1. Login to your Bolt installation
2. Go to "View/Install Extensions" (Hover over "Extras" menu item)
3. Type `user-profiles` into the input field
4. Click on the extension name
5. Click on "Browse Versions"
6. Click on "Install This Version" on the latest stable version

### Configuration

You can find the configuration file in `app\config\extensions` as `user-profiles.ohlandt.yml`.

There you can define new fields for your users, setup some options for the avatar helper and the public user profiles.

### Access Extended User Profile Form

Simply go to your normal profile edit page at `/bolt/profile`. It appears as an extra form below the standard user edit form.

Twig Helper Functions
---------------------

### Avatar

This extension provides an `avatar()` twig function to get the avatar URL for a given user.

##### Default usage:
```
<img src="{{ avatar(record.user) }}">
```

##### Override Gravatar size
If you have Gravatar fallback enabled, it uses `100` as default size.
You can override it with the second parameter.
```
<img src="{{ avatar(record.user, 50) }}">
```

##### Override default fallback URL
You can also override the default fallback URL you have set in the extension config.
```
<img src="{{ avatar(record.user, 50, 'https://domain.com/avatar.png') }}">
```

### Profile Link

This extension provides an `profile_link()` twig function to get the profile URL for a given user.

```
<a href="{{ profile_link(record.user) }}"></a>
```

### Has Profile

Depending on your settings in the extension config, an user might have a profile or not. The `has_profile()` function takes care of this and returns either true or false.

```
{% if has_profile(record.user) %}
  <a href="{{ profile_link(record.user) }}">{{ record.user.displayname }}</a>
{% else %}
  {{ record.user.displayname }}
{% endif %}
```

(For further information, check out the "Permissions" section below)

User Profiles
-------------

This extension provides the functionality to add user profiles to your website/blog/etc.
User profiles are enabled by default and try to use `profile.twig` as template but you can override everything in the extension config.

The user will be injected as `user` variable into your profile template.

##### Example: Get all Entries for the user
```
{% setcontent entries = 'entries' where {ownerid: user.id} %}
```

### Permissions

As of version `1.0.0` of this extension, public profiles can not only just turned on and off. There are a few different factors that decide if an user has a public profile or not. You can configure everything in your extension config.

If public profiles are turned on, the following settings will be used to determine if an user has a public profile or not. They will also be used for the `has_profile()` twig function.

##### Roles

```
roles: [ editor, chief-editor ]
```

The user has to have at least one of this roles to have a public profile.

##### Excluded Usernames

```
excluded_usernames: [ sahassar, gawain ]
```

Special users who shouldn't have a public profile.

##### Conditional Field

```
conditional_field: public_profile
```

A field on the user object which holds either a true'ish or false'ish value to determine if the user should have a public profile or not. **Example:** Create a checkbox field to let the user decide if he wants to have a public profile.

---

### License

This Bolt extension is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)
