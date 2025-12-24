<?php

// Note: Laravel will automatically resolve `Breadcrumbs::` without
// this import. This is nice for IDE syntax and refactoring.
use Diglactic\Breadcrumbs\Breadcrumbs;

// This import is also not required, and you could replace `BreadcrumbTrail $trail`
//  with `$trail`. This is nice for IDE type checking and completion.
use Diglactic\Breadcrumbs\Generator as BreadcrumbTrail;

/**
 * NOTE: Use route name for breadcrumbs name
 * Comment each breadcrumb page route
 *
 * Refer: https://github.com/diglactic/laravel-breadcrumbs#resourceful-controllers
 */

// Dashboard
Breadcrumbs::for('dashboard', function (BreadcrumbTrail $trail) {
    $trail->push(__('messages.dashboard'), route('dashboard'));
});

// Dashboard > My Account
Breadcrumbs::for('profile.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.my_account'), route('profile.index'));
});

// Dashboard > Change Password
Breadcrumbs::for('profile.change-password-form', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.change_password'), route('profile.change-password-form'));
});

// Dashboard > Change Email
Breadcrumbs::for('profile.change-email-form', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.change_email'), route('profile.change-email-form'));
});

// Dashboard > Change Password
Breadcrumbs::for('profile.change-username-form', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.change_username'), route('profile.change-username-form'));
});

// Dashboard > My Activities
Breadcrumbs::for('profile.activities', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.my_activities'), route('profile.activities'));
});

// Dashboard > Recent Sessions
Breadcrumbs::for('profile.recent-sessions', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.recent_sessions'), route('profile.recent-sessions'));
});

// Dashboard > Settings
Breadcrumbs::for('settings', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.settings'));
});

// Dashboard > Settings > Roles
Breadcrumbs::for('settings.roles.index', function (BreadcrumbTrail $trail) {
    $trail->parent('settings');
    $trail->push(__('messages.roles'), route('settings.roles.index'));
});

// Dashboard > Settings > Roles > Create Role
Breadcrumbs::for('settings.roles.create', function (BreadcrumbTrail $trail) {
    $trail->parent('settings.roles.index');
    $trail->push(__('messages.create_role'), route('settings.roles.create'));
});

// Dashboard > Settings > Roles > [Roles Name]
Breadcrumbs::for('settings.roles.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('settings.roles.index');
    $trail->push($model->display_name, route('settings.roles.show', $model->id));
});

// Dashboard > Settings > Roles > [Roles Name] > Edit Role
Breadcrumbs::for('settings.roles.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('settings.roles.index');
    $trail->push($model->display_name, route('settings.roles.show', $model->id));
    $trail->push(__('messages.edit_role'), route('settings.roles.edit', ['role' => $model->id]));
});

// Dashboard > Settings > Permissions
Breadcrumbs::for('settings.permissions.index', function (BreadcrumbTrail $trail) {
    $trail->parent('settings');
    $trail->push(__('messages.permissions'), route('settings.permissions.index'));
});

// Dashboard > Settings > Permissions > [Permissions Name]
Breadcrumbs::for('settings.permissions.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('settings.permissions.index');
    $trail->push($model->display_name, route('settings.permissions.show', $model->id));
});

// Dashboard > Settings > Permissions > [Permissions Name] > Edit Permission
Breadcrumbs::for('settings.permissions.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('settings.permissions.index');
    $trail->push($model->display_name, route('settings.permissions.show', $model->id));
    $trail->push(__('messages.edit_permission'), route('settings.permissions.edit', ['permission' => $model->id]));
});

// Dashboard > Settings > All User Activities
Breadcrumbs::for('settings.all-user-activities', function (BreadcrumbTrail $trail) {
    $trail->parent('settings');
    $trail->push(__('messages.all_user_activities'), route('settings.all-user-activities'));
});

// Dashboard > Settings > All User Sessions
Breadcrumbs::for('settings.all-user-sessions', function (BreadcrumbTrail $trail) {
    $trail->parent('settings');
    $trail->push(__('messages.all_user_sessions'), route('settings.all-user-sessions'));
});

// Dashboard > Users
Breadcrumbs::for('users.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.users'), route('users.index'));
});

// Dashboard > Users > Create User
Breadcrumbs::for('users.create', function (BreadcrumbTrail $trail) {
    $trail->parent('users.index');
    $trail->push(__('messages.create_user'), route('users.create'));
});

// Dashboard > Users > [Users Name]
Breadcrumbs::for('users.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('users.index');
    $trail->push($model->name, route('users.show', ['user' => $model->id]));
});

// Dashboard > Users > [Users Name] > Edit User
Breadcrumbs::for('users.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('users.show', $model);
    $trail->push(__('messages.edit_user'), route('users.edit', ['user' => $model->id]));
});

// Dashboard > Forms
Breadcrumbs::for('forms.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.forms'), route('forms.index'));
});

// Dashboard > Forms > Create Forms
Breadcrumbs::for('forms.create', function (BreadcrumbTrail $trail) {
    $trail->parent('forms.index');
    $trail->push(__('messages.create_forms'), route('forms.create'));
});

// Dashboard > Forms > [Forms Name]
Breadcrumbs::for('forms.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('forms.index');
    $trail->push($model->name, route('forms.show', ['form' => $model->id]));
});

// Dashboard > Forms > [Forms Name] > Edit
Breadcrumbs::for('forms.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('forms.show', $model);
    $trail->push(__('messages.edit'), route('forms.edit', ['form' => $model->id]));
});

// Dashboard > Forms > [Forms Name] > Submissions
Breadcrumbs::for('forms.submissions.index', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('forms.show', $model);
    $trail->push(__('messages.submissions'), route('forms.submissions.index', $model->id));
});

// Dashboard > Forms > [Form Name]
Breadcrumbs::for('forms.submissions.create', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('forms.index');
    $trail->push($model->name, route('forms.submissions.create', ['form' => $model->id]));
});

// Dashboard > Forms > [Forms Name] > Submissions > View
Breadcrumbs::for('submissions.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('forms.submissions.index', $model->form);
    $trail->push(__('messages.view'), route('submissions.show', $model->id));
});

// Dashboard > Forms > [Forms Name] > Submissions > Edit
Breadcrumbs::for('submissions.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('forms.submissions.index', $model->form);
    $trail->push(__('messages.edit'), route('submissions.edit', $model->id));
});

// Dashboard > Forms > [Forms Name] > Processes
Breadcrumbs::for('forms.processes.index', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('forms.show', $model);
    $trail->push(__('messages.workflow_processes'), route('forms.processes.index', $model->id));
});

// Dashboard > Groups
Breadcrumbs::for('groups.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.groups'), route('groups.index'));
});

// Dashboard > Groups > Create Group
Breadcrumbs::for('groups.create', function (BreadcrumbTrail $trail) {
    $trail->parent('groups.index');
    $trail->push(__('messages.create_group'), route('groups.create'));
});

// Dashboard > Groups > [Groups Name]
Breadcrumbs::for('groups.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('groups.index');
    $trail->push($model->name, route('groups.show', ['group' => $model->id]));
});

// Dashboard > Groups > [Groups Name] > Edit Groups
Breadcrumbs::for('groups.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('groups.show', $model);
    $trail->push(__('messages.edit_group'), route('groups.edit', ['group' => $model->id]));
});

// Dashboard > Form Types
Breadcrumbs::for('form-types.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.form_types'), route('form-types.index'));
});

// Dashboard > Form Types > Create Form Type
Breadcrumbs::for('form-types.create', function (BreadcrumbTrail $trail) {
    $trail->parent('form-types.index');
    $trail->push(__('messages.create_form_type'), route('form-types.create'));
});

// Dashboard > Form Types > [Form Type Name]
Breadcrumbs::for('form-types.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('form-types.index');
    $trail->push($model->name, route('form-types.show', $model->id));
});

// Dashboard > Form Types > [Form Type Name] > Edit Form Type
Breadcrumbs::for('form-types.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('form-types.index');
    $trail->push(__('messages.edit_form_type'), route('form-types.edit', ['form_type' => $model->id]));
});

// Dashboard > Notifications
Breadcrumbs::for('notifications.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.notifications'), route('notifications.index'));
});

// Dashboard > Notifications > [Notifications Name]
Breadcrumbs::for('notifications.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('notifications.index');
    $trail->push(__('messages.view_notification'), route('notifications.show', ['id' => $model->id]));
});

// Dashboard > My Submissions
Breadcrumbs::for('my-submissions.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.my_submissions'), route('my-submissions.index'));
});

// Dashboard > Submission List
Breadcrumbs::for('submission.list', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.submission_list'), route('submission.list'));
});

// Dashboard > My Current Tasks
Breadcrumbs::for('tasks.current', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.my_current_tasks'), route('tasks.current'));
});

// Dashboard > My Completed Tasks
Breadcrumbs::for('tasks.completed', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.my_completed_tasks'), route('tasks.completed'));
});

// Dashboard > My Submissions / Submission List / My Current Tasks / My Completed Tasks > [Form Name]
Breadcrumbs::for('my-submissions.show', function (BreadcrumbTrail $trail, $model, $parentBreadcrumbs) {
    $trail->parent($parentBreadcrumbs);
    $trail->push($model->form->name, route('submission.show', $model->id));
});

// Dashboard > Settings > System Settings
Breadcrumbs::for('settings.system.index', function (BreadcrumbTrail $trail) {
    $trail->parent('settings');
    $trail->push(__('messages.system_settings'), route('settings.system.index'));
});

// Dashboard > Abouts
Breadcrumbs::for('abouts.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.abouts'), route('samsulhadiss.abouts.index'));
});

// Dashboard > Abouts > Create About
Breadcrumbs::for('abouts.create', function (BreadcrumbTrail $trail) {
    $trail->parent('abouts.index');
    $trail->push(__('messages.create_about'), route('samsulhadiss.abouts.create'));
});

// Dashboard > Abouts > [About Name]
Breadcrumbs::for('abouts.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('abouts.index');
    $trail->push($model->name, route('samsulhadiss.abouts.show', $model->id));
});

// Dashboard > Abouts > [About Name] > Edit About
Breadcrumbs::for('abouts.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('abouts.index');
    $trail->push(__('messages.edit_about'), route('samsulhadiss.abouts.edit', ['about' => $model->id]));
});

// Dashboard > Articles
Breadcrumbs::for('articles.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.articles'), route('samsulhadiss.articles.index'));
});

// Dashboard > Articles > Create Article
Breadcrumbs::for('articles.create', function (BreadcrumbTrail $trail) {
    $trail->parent('articles.index');
    $trail->push(__('messages.create_article'), route('samsulhadiss.articles.create'));
});

// Dashboard > Articles > [Article Name]
Breadcrumbs::for('articles.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('articles.index');
    $trail->push($model->name, route('samsulhadiss.articles.show', $model->id));
});

// Dashboard > Articles > [Article Name] > Edit Article
Breadcrumbs::for('articles.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('articles.index');
    $trail->push(__('messages.edit_article'), route('samsulhadiss.articles.edit', ['article' => $model->id]));
});

// Dashboard > Events
Breadcrumbs::for('events.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.events'), route('samsulhadiss.events.index'));
});

// Dashboard > Events > Create Article
Breadcrumbs::for('events.create', function (BreadcrumbTrail $trail) {
    $trail->parent('events.index');
    $trail->push(__('messages.create_event'), route('samsulhadiss.events.create'));
});

// Dashboard > Events > [Event Name]
Breadcrumbs::for('events.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('events.index');
    $trail->push($model->name, route('samsulhadiss.events.show', $model->id));
});

// Dashboard > Events > [Event Name] > Edit Event
Breadcrumbs::for('events.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('events.index');
    $trail->push(__('messages.edit_event'), route('samsulhadiss.events.edit', ['event' => $model->id]));
});

// Dashboard > Services
Breadcrumbs::for('services.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.services'), route('samsulhadiss.services.index'));
});

// Dashboard > Services > Create Service
Breadcrumbs::for('services.create', function (BreadcrumbTrail $trail) {
    $trail->parent('services.index');
    $trail->push(__('messages.create_service'), route('samsulhadiss.services.create'));
});

// Dashboard > Services > [Service Name]
Breadcrumbs::for('services.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('services.index');
    $trail->push($model->name, route('samsulhadiss.services.show', $model->id));
});

// Dashboard > Services > [Service Name] > Edit Service
Breadcrumbs::for('services.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('services.index');
    $trail->push(__('messages.edit_service'), route('samsulhadiss.services.edit', ['service' => $model->id]));
});

// Dashboard > Testimonies
Breadcrumbs::for('testimonies.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.testimonies'), route('samsulhadiss.testimonies.index'));
});

// Dashboard > Testimonies > Create Testimony
Breadcrumbs::for('testimonies.create', function (BreadcrumbTrail $trail) {
    $trail->parent('testimonies.index');
    $trail->push(__('messages.create_testimony'), route('samsulhadiss.testimonies.create'));
});

// Dashboard > Testimonies > [Testimony Name]
Breadcrumbs::for('testimonies.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('testimonies.index');
    $trail->push($model->name, route('samsulhadiss.testimonies.show', $model->id));
});

// Dashboard > Testimonies > [Testimony Name] > Edit Testimony
Breadcrumbs::for('testimonies.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('testimonies.index');
    $trail->push(__('messages.edit_testimony'), route('samsulhadiss.testimonies.edit', ['testimony' => $model->id]));
});

// Dashboard > Projects
Breadcrumbs::for('projects.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.projects'), route('samsulhadiss.projects.index'));
});

// Dashboard > Projects > Create project
Breadcrumbs::for('projects.create', function (BreadcrumbTrail $trail) {
    $trail->parent('projects.index');
    $trail->push(__('messages.create_project'), route('samsulhadiss.projects.create'));
});

// Dashboard > Projects > [Project Name]
Breadcrumbs::for('projects.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('projects.index');
    $trail->push($model->name, route('samsulhadiss.projects.show', $model->id));
});

// Dashboard > Projects > [Project Name] > Edit Project
Breadcrumbs::for('projects.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('projects.index');
    $trail->push(__('messages.edit_project'), route('samsulhadiss.projects.edit', ['project' => $model->id]));
});

// Dashboard > Tools
Breadcrumbs::for('tools.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.tools'), route('samsulhadiss.tools.index'));
});

// Dashboard > Tools > Create Tool
Breadcrumbs::for('tools.create', function (BreadcrumbTrail $trail) {
    $trail->parent('tools.index');
    $trail->push(__('messages.create_tool'), route('samsulhadiss.tools.create'));
});

// Dashboard > Tools > [Tool Name]
Breadcrumbs::for('tools.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('tools.index');
    $trail->push($model->name, route('samsulhadiss.tools.show', $model->id));
});

// Dashboard > Tools > [Tool Name] > Edit Tool
Breadcrumbs::for('tools.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('tools.index');
    $trail->push(__('messages.edit_tool'), route('samsulhadiss.tools.edit', ['tool' => $model->id]));
});

// Dashboard > Courses
Breadcrumbs::for('courses.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.courses'), route('samsulhadiss.courses.index'));
});

// Dashboard > Courses > Create Course
Breadcrumbs::for('courses.create', function (BreadcrumbTrail $trail) {
    $trail->parent('courses.index');
    $trail->push(__('messages.create_course'), route('samsulhadiss.courses.create'));
});

// Dashboard > Courses > [Course Name]
Breadcrumbs::for('courses.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('courses.index');
    $trail->push($model->name, route('samsulhadiss.courses.show', $model->id));
});

// Dashboard > Courses > [Course Name] > Edit Course
Breadcrumbs::for('courses.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('courses.index');
    $trail->push(__('messages.edit_course'), route('samsulhadiss.courses.edit', ['course' => $model->id]));
});

// Dashboard > Farms
Breadcrumbs::for('farms.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.farms'), route('farms.farms.index'));
});

// Dashboard > Farms > Create Farms
Breadcrumbs::for('farms.create', function (BreadcrumbTrail $trail) {
    $trail->parent('farms.index');
    $trail->push(__('messages.create_farm'), route('farms.farms.create'));
});

// Dashboard > Farms > [Farms Name]
Breadcrumbs::for('farms.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('farms.index');
    $trail->push($model->name, route('farms.farms.show', $model->id));
});

// Dashboard > Farms > [Farm Name] > Edit Farm
Breadcrumbs::for('farms.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('farms.index');
    $trail->push(__('messages.edit_farm'), route('farms.farms.edit', ['farm' => $model->id]));
});

// Dashboard > Species
Breadcrumbs::for('species.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.species'), route('farms.species.index'));
});

// Dashboard > Species > Create Species
Breadcrumbs::for('species.create', function (BreadcrumbTrail $trail) {
    $trail->parent('species.index');
    $trail->push(__('messages.create_specie'), route('farms.species.create'));
});

// Dashboard > Species > [Species Name]
Breadcrumbs::for('species.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('species.index');
    $trail->push($model->name, route('farms.species.show', $model->id));
});

// Dashboard > Species > [Species Name] > Edit Species
Breadcrumbs::for('species.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('species.index');
    $trail->push(__('messages.edit_specie'), route('farms.species.edit', ['species' => $model->id]));
});

// Dashboard > Breeds
Breadcrumbs::for('breeds.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.breeds'), route('farms.breeds.index'));
});

// Dashboard > Breeds > Create Breeds
Breadcrumbs::for('breeds.create', function (BreadcrumbTrail $trail) {
    $trail->parent('breeds.index');
    $trail->push(__('messages.create_breed'), route('farms.breeds.create'));
});

// Dashboard > Breeds > [Breeds Name]
Breadcrumbs::for('breeds.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('breeds.index');
    $trail->push($model->name, route('farms.breeds.show', $model->id));
});

// Dashboard > Breeds > [Breeds Name] > Edit Breeds
Breadcrumbs::for('breeds.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('breeds.index');
    $trail->push(__('messages.edit_breed'), route('farms.breeds.edit', ['breed' => $model->id]));
});

// Dashboard > Enclosures
Breadcrumbs::for('enclosures.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.enclosures'), route('farms.enclosures.index'));
});

// Dashboard > Enclosures > Create Enclosures
Breadcrumbs::for('enclosures.create', function (BreadcrumbTrail $trail) {
    $trail->parent('enclosures.index');
    $trail->push(__('messages.create_enclosure'), route('farms.enclosures.create'));
});

// Dashboard > Enclosures > [Enclosures Name]
Breadcrumbs::for('enclosures.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('enclosures.index');
    $trail->push($model->name, route('farms.enclosures.show', $model->id));
});

// Dashboard > Enclosures > [Enclosures Name] > Edit Enclosures
Breadcrumbs::for('enclosures.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('enclosures.index');
    $trail->push(__('messages.edit_enclosure'), route('farms.enclosures.edit', ['enclosure' => $model->id]));
});

// Dashboard > Animals
Breadcrumbs::for('animals.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.animals'), route('farms.animals.index'));
});

// Dashboard > Animals > Create Animals
Breadcrumbs::for('animals.create', function (BreadcrumbTrail $trail) {
    $trail->parent('animals.index');
    $trail->push(__('messages.create_animal'), route('farms.animals.create'));
});

// Dashboard > Animals > [Animals Name]
Breadcrumbs::for('animals.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('animals.index');
    $trail->push($model->name, route('farms.animals.show', $model->id));
});

// Dashboard > Animals > [Animals Name] > Edit Animals
Breadcrumbs::for('animals.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('animals.index');
    $trail->push(__('messages.edit_animal'), route('farms.animals.edit', ['animal' => $model->id]));
});

// Dashboard > Animal Groups
Breadcrumbs::for('animal-groups.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.animal_groups'), route('farms.animal-groups.index'));
});

// Dashboard > Animal Groups > Create Animal Groups
Breadcrumbs::for('animal-groups.create', function (BreadcrumbTrail $trail) {
    $trail->parent('animal-groups.index');
    $trail->push(__('messages.create_animal_group'), route('farms.animal-groups.create'));
});

// Dashboard > Animal Groups > [Animal Groups Name]
Breadcrumbs::for('animal-groups.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('animal-groups.index');
    $trail->push(__('messages.animal_groups') . ' #' . $model->id, route('farms.animal-groups.show', $model->id));
});

// Dashboard > Animal Groups > [Animal Group Name] > Edit Animal Group
Breadcrumbs::for('animal-groups.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('animal-groups.index');
    $trail->push(__('messages.edit_animal_group'), route('farms.animal-groups.edit', ['animal_group' => $model->id]));
});

Breadcrumbs::for('barcodes.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('barcodes.index');
    $trail->push(__('messages.barcode_print'), route('farms.barcodes.show', ['barcode' => $model->id]));
});

Breadcrumbs::for('scanners.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.scanner_barcode'), route('scanners.scanner'));
});

// Dashboard > Barcodes
Breadcrumbs::for('barcodes.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.barcodes'), route('farms.barcodes.index'));
});

// Dashboard > Barcodes > Create Barcodes
Breadcrumbs::for('barcodes.create', function (BreadcrumbTrail $trail) {
    $trail->parent('barcodes.index');
    $trail->push(__('messages.create_barcode'), route('farms.barcodes.create'));
});


// Dashboard > Barcodes > [Barcode Name] > Edit Barcode
Breadcrumbs::for('barcodes.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('barcodes.index');
    $trail->push(__('messages.edit_barcode'), route('farms.barcodes.edit', ['barcode' => $model->id]));
});

// Dashboard > Feedings
Breadcrumbs::for('feedings.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.feedings'), route('farms.feedings.index'));
});

// Dashboard > Feedings > Create Feedings
Breadcrumbs::for('feedings.create', function (BreadcrumbTrail $trail) {
    $trail->parent('feedings.index');
    $trail->push(__('messages.create_feeding'), route('farms.feedings.create'));
});

// Dashboard > Feedings > [Feedings Name]
Breadcrumbs::for('feedings.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('feedings.index');
    $label = optional($model->date_time)->format('d/m/Y H:i') ?? __('messages.feedings');
    $trail->push($label, route('farms.feedings.show', $model->id));
});

// Dashboard > Feedings > [Feedings Name] > Edit Feeding
Breadcrumbs::for('feedings.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('feedings.index');
    $trail->push(__('messages.edit_feeding'), route('farms.feedings.edit', ['feeding' => $model->id]));
});

// Dashboard > Withdrawals
Breadcrumbs::for('withdrawals.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.withdrawals'), route('farms.withdrawals.index'));
});

// Dashboard > Withdrawals > Create Withdrawal
Breadcrumbs::for('withdrawals.create', function (BreadcrumbTrail $trail) {
    $trail->parent('withdrawals.index');
    $trail->push(__('messages.add_withdrawal'), route('farms.withdrawals.create'));
});

// Dashboard > Withdrawals > [Withdrawal]
Breadcrumbs::for('withdrawals.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('withdrawals.index');
    $trail->push($model->group_label, route('farms.withdrawals.show', $model->id));
});

// Dashboard > Withdrawals > [Withdrawal] > Edit
Breadcrumbs::for('withdrawals.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('withdrawals.index');
    $trail->push(__('messages.edit_withdrawal'), route('farms.withdrawals.edit', ['withdrawal' => $model->id]));
});

// Dashboard > Measurements
Breadcrumbs::for('measurements.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.measurements'), route('farms.measurements.index'));
});

// Dashboard > Measurements > Create Measurements
Breadcrumbs::for('measurements.create', function (BreadcrumbTrail $trail) {
    $trail->parent('measurements.index');
    $trail->push(__('messages.create_measurement'), route('farms.measurements.create'));
});

// Dashboard > Measurements > [Measurements Name]
Breadcrumbs::for('measurements.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('measurements.index');
    $label = optional($model->measured_at)->format('d/m/Y H:i') ?? __('messages.measurements');
    $trail->push($label, route('farms.measurements.show', $model->id));
});

// Dashboard > Measurements > [Measurements Name] > Edit Measurement
Breadcrumbs::for('measurements.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('measurements.index');
    $trail->push(__('messages.edit_measurement'), route('farms.measurements.edit', ['measurement' => $model->id]));
});

// Dashboard > Mortalities
Breadcrumbs::for('mortalities.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.mortalities'), route('farms.mortalities.index'));
});

// Dashboard > Mortalities > Create Mortalities
Breadcrumbs::for('mortalities.create', function (BreadcrumbTrail $trail) {
    $trail->parent('mortalities.index');
    $trail->push(__('messages.create_mortality'), route('farms.mortalities.create'));
});

// Dashboard > Mortalities > [Mortalities Name]
Breadcrumbs::for('mortalities.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('mortalities.index');
    $label = $model->subject_label ?? __('messages.mortalities');
    $trail->push($label, route('farms.mortalities.show', $model->id));
});

// Dashboard > Mortalities > [Mortalities Name] > Edit Mortality
Breadcrumbs::for('mortalities.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('mortalities.index');
    $trail->push(__('messages.edit_mortality'), route('farms.mortalities.edit', ['mortality' => $model->id]));
});

// Dashboard > Treatments
Breadcrumbs::for('treatments.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.treatments'), route('farms.treatments.index'));
});

// Dashboard > Treatments > Create Treatments
Breadcrumbs::for('treatments.create', function (BreadcrumbTrail $trail) {
    $trail->parent('treatments.index');
    $trail->push(__('messages.create_treatment'), route('farms.treatments.create'));
});

// Dashboard > Treatments > [Treatments Name]
Breadcrumbs::for('treatments.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('treatments.index');
    $label = $model->subject_label ?? $model->product ?? __('messages.treatments');
    $trail->push($label, route('farms.treatments.show', $model->id));
});

// Dashboard > Treatments > [Treatments Name] > Edit Treatment
Breadcrumbs::for('treatments.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('treatments.index');
    $trail->push(__('messages.edit_treatment'), route('farms.treatments.edit', ['treatment' => $model->id]));
});

// Dashboard > Eggs
Breadcrumbs::for('eggs.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.eggs'), route('farms.eggs.index'));
});

// Dashboard > Eggs > Create Eggs
Breadcrumbs::for('eggs.create', function (BreadcrumbTrail $trail) {
    $trail->parent('eggs.index');
    $trail->push(__('messages.create_egg'), route('farms.eggs.create'));
});

// Dashboard > Eggs > [Eggs Name]
Breadcrumbs::for('eggs.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('eggs.index');
    $trail->push($model->notes, route('farms.eggs.show', $model->id));
});

// Dashboard > Eggs > [Eggs Name] > Edit Egg
Breadcrumbs::for('eggs.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('eggs.index');
    $trail->push(__('messages.edit_egg'), route('farms.eggs.edit', ['egg' => $model->id]));
});

// Dashboard > Hatches
Breadcrumbs::for('hatches.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.hatches'), route('farms.hatches.index'));
});

// Dashboard > Hatches > Create Hatches
Breadcrumbs::for('hatches.create', function (BreadcrumbTrail $trail) {
    $trail->parent('hatches.index');
    $trail->push(__('messages.create_hatch'), route('farms.hatches.create'));
});

// Dashboard > Hatches > [Hatches Name]
Breadcrumbs::for('hatches.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('hatches.index');
    $label = $model->incubator_label ?? __('messages.hatches');
    $trail->push($label, route('farms.hatches.show', $model->id));
});

// Dashboard > Hatches > [Hatches Name] > Edit Hatch
Breadcrumbs::for('hatches.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('hatches.index');
    $trail->push(__('messages.edit_hatch'), route('farms.hatches.edit', ['hatch' => $model->id]));
});

// Dashboard > Workflow Template Types
Breadcrumbs::for('workflow-template-types.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.workflow_template_types'), route('automations.workflow-template-types.index'));
});

// Dashboard > Workflow Template Types > Create Workflow Template Types
Breadcrumbs::for('workflow-template-types.create', function (BreadcrumbTrail $trail) {
    $trail->parent('workflow-template-types.index');
    $trail->push(__('messages.create_workflow_template_type'), route('automations.workflow-template-types.create'));
});

// Dashboard > Workflow Template Types > [Workflow Template Types Name]
Breadcrumbs::for('workflow-template-types.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('workflow-template-types.index');
    $trail->push($model->name, route('automations.workflow-template-types.show', $model->id));
});

// Dashboard > Workflow Template Types > [Workflow Template Type Name] > Edit Workflow Template Type
Breadcrumbs::for('workflow-template-types.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('workflow-template-types.index');
    $trail->push(__('messages.edit_workflow_template_type'), route('automations.workflow-template-types.edit', ['workflow_template_type' => $model->id]));
});

// Dashboard > Workflow Templates
Breadcrumbs::for('workflow-templates.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.workflow_template'), route('automations.workflow-templates.index'));
});

// Dashboard > Workflow Templates > Create Workflow Templates
Breadcrumbs::for('workflow-templates.create', function (BreadcrumbTrail $trail) {
    $trail->parent('workflow-templates.index');
    $trail->push(__('messages.create_workflow_template'), route('automations.workflow-templates.create'));
});

// Dashboard > Workflow Templates > [Workflow Templates Name]
Breadcrumbs::for('workflow-templates.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('workflow-templates.index');
    $trail->push($model->name, route('automations.workflow-templates.show', $model->id));
});

// Dashboard > Workflow Templates > [Workflow Template Name] > Edit Workflow Template
Breadcrumbs::for('workflow-templates.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('workflow-templates.index');
    $trail->push(__('messages.edit_workflow_template'), route('automations.workflow-templates.edit', ['workflow_template' => $model->id]));
});

// Dashboard > Bot Types
Breadcrumbs::for('bot-types.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.bot_type'), route('automations.bot-types.index'));
});

// Dashboard > Bot Types > Create Bot Types
Breadcrumbs::for('bot-types.create', function (BreadcrumbTrail $trail) {
    $trail->parent('bot-types.index');
    $trail->push(__('messages.create_bot_type'), route('automations.bot-types.create'));
});

// Dashboard > Bot Types > [Bot Types Name]
Breadcrumbs::for('bot-types.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('bot-types.index');
    $trail->push($model->name, route('automations.bot-types.show', $model->id));
});

// Dashboard > Bot Types > [Bot Type Name] > Edit Bot Type
Breadcrumbs::for('bot-types.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('bot-types.index');
    $trail->push(__('messages.edit_bot_type'), route('automations.bot-types.edit', ['bot_type' => $model->id]));
});

// Dashboard > Bots
Breadcrumbs::for('bots.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.bot'), route('automations.bots.index'));
});

// Dashboard > Digital Products
Breadcrumbs::for('product-digitals.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.digital_products'), route('catalogs.product-digitals.index'));
});

// Dashboard > Digital Products > Create
Breadcrumbs::for('product-digitals.create', function (BreadcrumbTrail $trail) {
    $trail->parent('product-digitals.index');
    $trail->push(__('messages.add_product_digitals'), route('catalogs.product-digitals.create'));
});

// Dashboard > Digital Products > [Product]
Breadcrumbs::for('product-digitals.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('product-digitals.index');
    $trail->push($model->title, route('catalogs.product-digitals.show', $model->id));
});

// Dashboard > Digital Products > [Product] > Edit
Breadcrumbs::for('product-digitals.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('product-digitals.show', $model);
    $trail->push(__('messages.edit'), route('catalogs.product-digitals.edit', $model->id));
});

Breadcrumbs::for('product-appointments.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.appointment_products'), route('catalogs.product-appointments.index'));
});

Breadcrumbs::for('product-appointments.create', function (BreadcrumbTrail $trail) {
    $trail->parent('product-appointments.index');
    $trail->push(__('messages.add_product_appointments'), route('catalogs.product-appointments.create'));
});

Breadcrumbs::for('product-appointments.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('product-appointments.index');
    $trail->push($model->title, route('catalogs.product-appointments.show', $model->id));
});

Breadcrumbs::for('product-appointments.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('product-appointments.show', $model);
    $trail->push(__('messages.edit'), route('catalogs.product-appointments.edit', $model->id));
});

Breadcrumbs::for('product-appointments.schedule', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('product-appointments.show', $model);
    $trail->push(__('messages.manage_schedule'), route('catalogs.product-appointments.schedule', $model->id));
});

Breadcrumbs::for('inventory.items.index', function (BreadcrumbTrail $trail) {
    $trail->parent('farms.index');
    $trail->push(__('messages.inventory_items'), route('farms.inventory.items.index'));
});

Breadcrumbs::for('inventory.items.create', function (BreadcrumbTrail $trail) {
    $trail->parent('inventory.items.index');
    $trail->push(__('messages.create_inventory_item'), route('farms.inventory.items.create'));
});

Breadcrumbs::for('inventory.items.show', function (BreadcrumbTrail $trail, $item) {
    $trail->parent('inventory.items.index');
    $trail->push($item->name, route('farms.inventory.items.show', $item->id));
});

Breadcrumbs::for('inventory.items.edit', function (BreadcrumbTrail $trail, $item) {
    $trail->parent('inventory.items.show', $item);
    $trail->push(__('messages.edit'), route('farms.inventory.items.edit', $item->id));
});

Breadcrumbs::for('inventory.movements.index', function (BreadcrumbTrail $trail) {
    $trail->parent('farms.index');
    $trail->push(__('messages.inventory_movements'), route('farms.inventory.movements.index'));
});

Breadcrumbs::for('inventory.movements.create', function (BreadcrumbTrail $trail) {
    $trail->parent('inventory.movements.index');
    $trail->push(__('messages.create_inventory_movement'), route('farms.inventory.movements.create'));
});

Breadcrumbs::for('inventory.movements.edit', function (BreadcrumbTrail $trail, $movement) {
    $trail->parent('inventory.movements.index');
    $trail->push(__('messages.edit_inventory_movement'), route('farms.inventory.movements.edit', $movement->id));
});

Breadcrumbs::for('product-physicals.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.physical_products'), route('catalogs.product-physicals.index'));
});

Breadcrumbs::for('product-physicals.create', function (BreadcrumbTrail $trail) {
    $trail->parent('product-physicals.index');
    $trail->push(__('messages.add_product_physicals'), route('catalogs.product-physicals.create'));
});

Breadcrumbs::for('product-physicals.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('product-physicals.index');
    $trail->push($model->title, route('catalogs.product-physicals.show', $model->id));
});

Breadcrumbs::for('product-physicals.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('product-physicals.show', $model);
    $trail->push(__('messages.edit'), route('catalogs.product-physicals.edit', $model->id));
});

// Dashboard > Companies
Breadcrumbs::for('companies.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.companies'), route('legals.companies.index'));
});

// Dashboard > Companies > Create
Breadcrumbs::for('companies.create', function (BreadcrumbTrail $trail) {
    $trail->parent('companies.index');
    $trail->push(__('messages.create_company'), route('legals.companies.create'));
});

// Dashboard > Companies > [Company]
Breadcrumbs::for('companies.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('companies.index');
    $trail->push($model->name, route('legals.companies.show', $model->id));
});

// Dashboard > Companies > [Company] > Edit
Breadcrumbs::for('companies.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('companies.show', $model);
    $trail->push(__('messages.edit_company'), route('legals.companies.edit', $model->id));
});

// Dashboard > Brands
Breadcrumbs::for('brands.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.brands'), route('legals.brands.index'));
});

// Dashboard > Brands > Create
Breadcrumbs::for('brands.create', function (BreadcrumbTrail $trail) {
    $trail->parent('brands.index');
    $trail->push(__('messages.create_brand'), route('legals.brands.create'));
});

// Dashboard > Brands > [Brand]
Breadcrumbs::for('brands.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('brands.index');
    $trail->push($model->name, route('legals.brands.show', $model->id));
});

// Dashboard > Brands > [Brand] > Edit
Breadcrumbs::for('brands.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('brands.show', $model);
    $trail->push(__('messages.edit_brand'), route('legals.brands.edit', $model->id));
});

// Dashboard > Brand Categories
Breadcrumbs::for('brand-categories.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.brand_categories'), route('legals.brand-categories.index'));
});

// Dashboard > Brand Categories > Create
Breadcrumbs::for('brand-categories.create', function (BreadcrumbTrail $trail) {
    $trail->parent('brand-categories.index');
    $trail->push(__('messages.create_brand_category'), route('legals.brand-categories.create'));
});

// Dashboard > Brand Categories > [Category]
Breadcrumbs::for('brand-categories.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('brand-categories.index');
    $trail->push($model->name, route('legals.brand-categories.show', $model->id));
});

// Dashboard > Brand Categories > [Category] > Edit
Breadcrumbs::for('brand-categories.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('brand-categories.show', $model);
    $trail->push(__('messages.edit_brand_category'), route('legals.brand-categories.edit', $model->id));
});

// Dashboard > Bots > Create Bots
Breadcrumbs::for('bots.create', function (BreadcrumbTrail $trail) {
    $trail->parent('bots.index');
    $trail->push(__('messages.create_bot'), route('automations.bots.create'));
});

// Dashboard > Bots > [Bots Name]
Breadcrumbs::for('bots.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('bots.index');
    $trail->push($model->name, route('automations.bots.show', $model->id));
});

// Dashboard > Bots > [Bot Name] > Edit Bot
Breadcrumbs::for('bots.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('bots.index');
    $trail->push(__('messages.edit_bot'), route('automations.bots.edit', ['bot' => $model->id]));
});

// Dashboard > Bookings
Breadcrumbs::for('bookings.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.booking'), route('automations.bookings.index'));
});

// Dashboard > Bookings > Create Bookings
Breadcrumbs::for('bookings.create', function (BreadcrumbTrail $trail) {
    $trail->parent('bookings.index');
    $trail->push(__('messages.create_booking'), route('automations.bookings.create'));
});

// Dashboard > Bookings > [Bookings Name]
Breadcrumbs::for('bookings.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('bookings.index');
    $trail->push($model->name, route('automations.bookings.show', $model->id));
});

// Dashboard > Bookings > [Booking Name] > Edit Booking
Breadcrumbs::for('bookings.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('bookings.index');
    $trail->push(__('messages.edit_booking'), route('automations.bookings.edit', ['booking' => $model->id]));
});

// Dashboard > Faqs
Breadcrumbs::for('faqs.index', function (BreadcrumbTrail $trail) {
    $trail->parent('dashboard');
    $trail->push(__('messages.faq'), route('automations.faqs.index'));
});

// Dashboard > Faqs > Create Faqs
Breadcrumbs::for('faqs.create', function (BreadcrumbTrail $trail) {
    $trail->parent('faqs.index');
    $trail->push(__('messages.create_faq'), route('automations.faqs.create'));
});

// Dashboard > Faqs > [Faqs Name]
Breadcrumbs::for('faqs.show', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('faqs.index');
    $trail->push($model->date, route('automations.faqs.show', $model->id));
});

// Dashboard > faqs > [Faq Name] > Edit Faq
Breadcrumbs::for('faqs.edit', function (BreadcrumbTrail $trail, $model) {
    $trail->parent('faqs.index');
    $trail->push(__('messages.edit_faq'), route('automations.faqs.edit', ['faq' => $model->id]));
});
