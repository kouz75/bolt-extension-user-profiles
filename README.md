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

### Avatar helper

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

### User profile link helper

This extension provides an `profile_link()` twig function to get the profile URL for a given user.

```
<a href="{{ profile_link(record.user) }}"></a>
```

### User profiles

This extension provides the functionality to add user profiles to your website/blog/etc.
User profiles are enabled by default and try to use `profile.twig` as template but you can override everything in the extension config.

The user will be injected as `user` variable into your profile template.

##### Example: Get all Entries for the user
```
{% setcontent entries = 'entries' where {ownerid: user.id} %}
```

---

### License

This Bolt extension is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)