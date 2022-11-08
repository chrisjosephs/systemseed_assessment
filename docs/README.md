<h2>Additions:</h2>
Async protection to prevent user being able to submit new checkbox saves before current save is complete: you cannot
cause a race condition clicking and unclicking a checkbox too quickly which could async in the wrong order, since during
the POST/PATCH request to save the todolist, I made the checkbox input disabled whilst a save is taking place.

It will also disable checkbox clicks until the xcsrf session header has been grabbed (which is the first thing that
happens) as you need for this auth to save

Invalidate cache tags upon save todolistitem so page refresh will show changes immediately

Anonymous users will not be able to alter checkboxes, but can still see content as far as the default config is
concerned (though where this module is installed might say different, and that has been accounted for to satisfy "-
Permission to modify state of To-Do items (not the node!) should be given only to the users who have access to view the
checklist" part of spec).

Similarly PATCHing as unauthenticated user, will 401 forbidden on the backend.

This was done from "cookie" authentication method since the user would be logged into drupal already to access the
application jsx.

<h2>Log:</h2>
<li>
set up environment - minimal.  can just use valet for this, not docker image for this as no target environment reqs to match local environment to given (apart from D9).. hmm I could have used same image in the .circleci but didn't look at that until later, so I just matched the php version and cli
</li>
<li>install admin theme, claro nicer and less buggy than adminimal now, gin nice for users but not so clinical for devs</li>
<li>drush theme:enable claro</li>
<li>drush config-set system.theme admin claro</li>
<li>drush en toolbar</li>
<li>.\vendor\drupal\console\bin\drupal site:mode dev
Unpkg librararies in libraries.yml pointing at non-existing version resources.  Fixed.
</li>
<br/>
<li> default theme bartik to match  protected $defaultTheme = 'bartik'; inside the HealthCheckTest so should match testers' machine although that's never used in practice.. Actually I did put a fix in .todo-list__item label p:first-of-type {
  /* default p has 1.2em bottom so need top to keep v center */
  padding: 1.2em 0 0;
}
for vertical alignment being off on bartik that when I used the new default d9 theme is not broken on other themes so I removed that again<br/>
<br/>
drush theme:enable bartik<br/>
drush config-set system.theme default bartik</li>
<li> fix styling so label inline with checkboxes, including fix for very long multiline labels</li>
<li> add rest resource to submit changes from js since this is preferred to Controllers nowadays:<br/>
<br/>
.\vendor\drupal\console\bin\drupal generate:plugin:rest:resource<br/>
<br/>
add views and rest dependencies to systemseed_assessment.info.yml. <br/>
<br/>
add rest config \modules\contrib\systemseed_assessment\config\install\rest.resource.todolist.yml
drush cim -y --partial --source=modules/contrib/systemseed_assessment/config/install<br/>
</li>
so that can test with postman by going to /user/login?_format=json:

add basic_auth and hal dependencies to systemseed_assessment.info.yml
Styling: so we could have the js bring in the styling or drupal bring in the styling css, but since we are not
precompiling this, and it is not a standalone/headless app, and Drupal is already bringing in resources from
libraries.yml then will use Drupal to manage the css

I added "systemseed_assement.install" for uninstall function so I can test uninstalling and reinstalling the module,
with hook to remove the old config first so no conflict reinstalling it. And tested reinstalling sets up the rest
resource config correctly.

Configured Rest resource payload to be a request object, as the template created by drupal generate:plugin:rest:resource
was incorrect. And tested in postman after geting CSRF session cookie

Moving back to application.js code, added X-CSRF token fetcher needed for any fetch request.

See gitlog of my own repo I created to track rest of work prior to creating patch to
submit (https://github.com/chrisjosephs/systemseed_assessment/commits?author=chrisjosephs), well I put that private now
because I realised you probably don't want anyone else finding it and cheating, although "SystemSeed" isn't a
collaborator I can add, so you will have to ask me with a github Username if you want to see that.

Ran code sniffer. And circle ci test.

composer require --dev dealerdirect/phpcodesniffer-composer-installer && \
composer require --dev phpspec/prophecy-phpunit:^2

.\vendor\bin\phpcs --config-set installed_paths C:\Users\xis23\AppData\Roaming\Composer\vendor\drupal\coder\coder_sniffer,C:\Users\xis23\AppData\Roaming\Composer\vendor\slevomat\coding-standard
.\vendor\bin\phpcs --standard=Drupal --ignore=node_modules,vendor,dist,js web\modules\contrib\systemseed_assessment\

<h2>Observations:</h2>

The todo list label does not have a character cap (therefore tested one item with something like 4000 chars just to see
what happens: only real prob is it can be so long when the checkbox is vertically aligned to the center of it then you
would have to scroll further down the screen to even see the checkbox)

Added item to test html tags are not rendered as Drupal states on the admin > content > edit paragraph page, even with
the dangerouslysetinnerhtml in the application.js renderer, and this is ok you just see the unparsed < tags /> as
expected

