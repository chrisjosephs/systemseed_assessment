<h2>Log:</h2>
<li>
set up environment - minimal.  can just use valet for this, not docker image for this as no target environment reqs to match local environment to given (apart from D9)
</li>
<li>install admin theme, claro nicer and less buggy than adminimal now, gin nice for users but not so clinical for devs</li>
<li>drush theme:enable claro</li>
<li>drush config-set system.theme admin claro</li>
<li>drush en toolbar</li>
<li>.\vendor\drupal\console\bin\drupal site:mode dev
Unpkg librararies in libraries.yml pointing at non-existing version resources.  Fixed.
</li>
<br/>
<li> made sure default theme bartik to match  protected $defaultTheme = 'bartik'; inside the HealthCheckTest so should match testers' machine<br/>
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

add basic_auth and hal dependencies to  systemseed_assessment.info.yml
Styling: so we could have the js bring in the styling or drupal bring in the styling css, but since we are not precompiling this, and it is not a standalone/headless app, and Drupal is already bringing in resources from libraries.yml then will use Drupal to manage the css

I added "systemseed_assement.install" for uninstall function so I can test uninstalling and reinstalling the module, with hook to remove the old config first so no conflict reinstalling it

Observations:

The todo list does not have a character cap (therefore tested one item with something like 4000 chars just to see what happens: only real prob is it can be so long when the checkbox is vertically aligned to the center of it then you would have to scroll further down the screen  to even see the checkbox)

Added item to test html tags are not rendered as Drupal states on the admin > content > edit paragraph page, even with the dangerouslysetinnerhtml in the application.js renderer, and this is ok you just see the unparsed < tags /> as expected
