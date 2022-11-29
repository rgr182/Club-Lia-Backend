<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\UserSubscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/ 
Route::post('login', 'API\UserController@login');
Route::post('register', 'API\UserController@register');
Route::post('userSubscription', 'UserSubscriptionController@store');
Route::put('updateSubscription/{id}', 'UserSubscriptionController@updateOrder');
Route::post('teacher', 'TeacherValidationsController@register');
Route::post('fileUpload/{id}', 'TeacherValidationsController@fileUpload');


Route::post('/acceptPayment', [ 'as' => 'acceptPayment', function()
{
    return app()->make(UserSubscriptionController::class)->callAction('acceptPayment', $parameters = [ 'topic' => request()->topic, 'id' => request()->id ]);
}]);
Route::post('teacherRegister', 'UserSubscriptionController@sendMailFromTeachers');
Route::post('studentSubscription', 'UserSubscriptionController@storeChildren');
Route::post('schoolSubscription', 'UserSubscriptionController@sendMailToStoreSchool');
Route::post('getPreapproval/{id}', 'UserSubscriptionController@getPreapproval');
Route::post('getPreapprovalCourse/{id}', 'UserSubscriptionController@getPreapprovalCourse');
Route::post('parentSubscription', 'UserSubscriptionController@storeParent');
Route::post('supportSubscription', 'UserSubscriptionController@storeSupport');
Route::post('sponsorshipSubscription', 'UserSubscriptionController@storeSponsorship');
Route::get('metodos/pago', 'MercadoPagoController@listPaymentMethods');
Route::post('pago/membresia', 'MercadoPagoController@processPayment');
Route::post('teacher/create', 'TeacherValidationsController@create');
Route::post('file', 'HomeworkController@fileUploadTeacher');

Route::get('donors', 'UserSubscriptionController@donorList');

Route::get('/thinkific/courses/{id}', 'UserThinkificController@listCourses');

Route::get('membresia', 'LicenseTypeController@index');
Route::get('membresia/{id}', 'LicenseTypeController@show');

Route::get('login/google/redirect/{id}/{returnToPath}', 'CalendarAPIController@redirect');

Route::get('login/google/callback', 'CalendarAPIController@callback');

Route::group(['middleware' => 'auth:api'], function(){
    Route::post('logout', 'API\UserController@logout');
    Route::post('access-token', 'API\UserController@accessToken');

    Route::get('escuelas', 'SchoolController@index');
    Route::get('escuelas/{id}', 'SchoolController@show');
    Route::post('escuelas', 'SchoolController@store');
    Route::put('escuelas/{id}', 'SchoolController@update');
    Route::delete('escuelas/{id}', 'SchoolController@destroy');
    Route::get('adminSchool', 'SchoolController@getAdminSchool');
    Route::get('adminCompanies', 'SchoolController@getCompanies');
    Route::get('users-by-company', 'SchoolController@getUsersByCompany');
    /**
     * Groups
     */
    Route::get('grupos', 'GroupController@index');
    Route::get('grupos/{id}', 'GroupController@show');
    Route::post('grupos/crear', 'GroupController@store');
    Route::put('grupos/update', 'GroupController@update');
    Route::delete('grupos/{id}', 'GroupController@destroy');
    Route::post('grupos/studentsByGroup', 'GroupController@studentsByGroup');
    Route::post('duplicar/grupo/{id}', 'GroupController@duplicateGroup');
    Route::get('clases', 'GroupController@clasesInfo');

    Route::get('grupoestudiante/{id}', 'GroupStudentController@show');
    Route::post('grupoestudiante/crear', 'GroupStudentController@store');
    Route::post('alumno/grupos/eliminar', 'GroupStudentController@removeStudentGroups');
    Route::post('alumnos/grupos/eliminar', 'GroupStudentController@removeStudentsGroups');
    Route::get('groupousuarios/{id}', 'GroupStudentController@getGroupUsers');
    Route::post('user/to/groups', 'GroupStudentController@groupMultipleEnroll');
    Route::get('user/get-activities/{userId}', 'UserController@getUserActitivies');

    Route::get('periodos', 'PeriodoController@index');
    Route::get('periodos/{id}', 'PeriodoController@show');
    Route::post('periodos', 'PeriodoController@store');
    Route::put('periodos/{id}', 'PeriodoController@update');
    Route::delete('periodos/{id}', 'PeriodoController@destroy');

    Route::get('inscripciones', 'EnrollmentController@index');
    Route::get('inscripciones/{id}', 'EnrollmentController@show');
    Route::post('inscripciones', 'EnrollmentController@store');
    Route::put('inscripciones/{id}', 'EnrollmentController@update');
    Route::delete('inscripciones/{id}', 'EnrollmentController@destroy');

    Route::get('usuarios', 'UserController@index');
    Route::get('usuarios/{uuid}', ['as' => 'usuarios/{uuid}', 'uses'=>'UserController@show']);
    Route::post('usuarios', 'UserController@store');
    Route::put('usuarios/{uuid}', 'UserController@update');
    Route::delete('usuarios/{uuid}', 'UserController@destroy');
    Route::post('multiusuarios/', 'UserController@multiDestroy');
    Route::get('maestros', 'UserController@listTeachers');
    Route::get('padresHijos', 'UserController@listTutorsStudents');
    Route::get('students', 'UserController@getStudents');
    Route::get('studentaverage/{id}', 'UserController@getStudentAverage');
    Route::get('student/groupsisnotin/{id}', 'UserController@studentIsNotInGroups');
    Route::get('student/groups/{id}', 'UserController@groupListStudent');

    Route::get('studentsT', 'UserController@studentList');



    Route::put('usuariosgroup', 'UserController@updateGroup');

    Route::get('cuenta', 'ManageAccountController@index');
    Route::put('cuenta/{uuid}', 'ManageAccountController@update');

    Route::post('membresia', 'LicenseTypeController@store');
    Route::put('membresia/{id}', 'LicenseTypeController@update');
    Route::delete('membresia/{id}', 'LicenseTypeController@destroy');

    Route::get('licencias', 'LicenseController@index');
    Route::get('licencias/{id}', 'LicenseController@show');
    Route::post('licencias', 'LicenseController@store');
    Route::put('licencias/{id}', 'LicenseController@update');
    Route::delete('licencias/{id}', 'LicenseController@destroy');

    Route::post('emails', 'MassiveEmailController@send');

    //Asignar Licencias
    Route::post('asignar/licencias', 'UserController@assignLicense');

    Route::get('key/licencias', 'LicenseKeyController@index');
    Route::get('key/licencias/{id}', 'LicenseKeyController@show');
    Route::post('key/licencias', 'LicenseKeyController@store');
    Route::put('key/licencias/{id}', 'LicenseKeyController@update');
    Route::delete('key/licencias/{id}', 'LicenseKeyController@destroy');

    Route::get('tipos/contacto', 'ContactTypeController@index');
    Route::get('tipos/contacto/{id}', 'ContactTypeController@show');
    Route::post('tipos/contacto', 'ContactTypeController@store');
    Route::put('tipos/contacto/{id}', 'ContactTypeController@update');
    Route::delete('tipos/contacto/{id}', 'ContactTypeController@destroy');

    Route::get('grados', 'GradeController@index');
    Route::get('grados/{id}', 'GradeController@show');
    Route::post('grados', 'GradeController@store');
    Route::put('grados/{id}', 'GradeController@update');
    Route::delete('grados/{id}', 'GradeController@destroy');

    Route::get('contacto', 'ContactController@index');
    Route::get('contacto/{id}', 'ContactController@show');
    Route::post('contacto', 'ContactController@store');
    Route::put('contacto/{id}', 'ContactController@update');
    Route::delete('contacto/{id}', 'ContactController@destroy');

    Route::get('roles', 'RoleController@index');
    Route::get('roles/{id}', 'RoleController@show');
    Route::post('roles', 'RoleController@store');
    Route::put('roles/{id}', 'RoleController@update');
    Route::delete('roles/{id}', 'RoleController@destroy');

    Route::get('actividades', 'ActivityController@index');
    Route::get('actividades/actividad/{id}', 'ActivityController@show');
    Route::post('actividades', 'ActivityController@store');
    Route::put('actividades/{id}', 'ActivityController@update');
    Route::delete('actividades/{id}', 'ActivityController@destroy');
    Route::get('actividades/grupos', 'ActivityController@getGroups');
    Route::get('actividades/materias', 'ActivityController@getCustomSubjects');

    Route::get('tareas', 'HomeworkController@index');
    Route::get('tarea/{id}', 'HomeworkController@getHomework');
    Route::get('tareas/actividad/{id}', 'HomeworkController@getHomeworks');
    Route::get('tareas/entregadas', 'HomeworkController@getDelivered');
    Route::get('tareas/grupos', 'HomeworkController@getGroupHomeworks');
    Route::get('tareas/{id}', 'HomeworkController@show');
    Route::post('tareas', 'HomeworkController@store');
    Route::put('tareas/{id}', 'HomeworkController@update');
    Route::delete('tareas/{id}', 'HomeworkController@destroy');

    Route::post('badges', 'BadgeController@badge');
    Route::get('getBadge', 'BadgeController@verBadge');

    Route::get('schools', 'LiaSchoolController@list');
    Route::get('lia-schools-sync', 'LiaSchoolController@sync');

    Route::post('importar/usuarios', 'UserImportController@store');

    //THINKIFIC ROUTES
    Route::get('/usuario/thinkific', 'UserThinkificController@getUsers');
    //Route::get('/thinkific/courses/{keyword}', 'UserThinkificController@listCourses');

    //Enrollment
    Route::post('cursos/registro', 'UserThinkificController@enrollment');

    //Route::post('/usuario/comunidad', 'UserPhpFoxController@getToken');
    Route::post('/usuario/comunidad', 'UserPhpFoxController@getToken');
    Route::post('/comunidad/nuevo/usuario', 'UserPhpFoxController@storeUser');
    Route::post('/comunidad/{user_id}', 'UserPhpFoxController@destroy');

    Route::post('/sincronizar/usuario/', 'SyncUserPlatformController@syncUserplatform');

    Route::post('/usuario/login/', 'UserThinkificController@singleSignThinkific');

    Route::put('/actualizar/usuarios/{id}', 'SyncUserPlatformController@updateUser');
    Route::put('/update/roleP', 'SyncUserPlatformController@rolePrincipito');

    Route::post('sync/usuario/', 'UserThinkificController@syncUser');
    Route::post('platform/usuario/', 'UserThinkificController@syncUserplatform');

    Route::post('/usuario_t/login/', 'UserThinkificController@singleSignThinkific');
    Route::get('/thinkific/userCourses', 'UserThinkificController@getUserCourses');
    Route::get('/thinkific/childCourses', 'UserThinkificController@getChildCourses');

    Route::post('/usuario_p/login/', 'UserPhpFoxController@singleSignPhpFox');
    Route::post('/usuario_p/getRoute/', 'UserPhpFoxController@PhpFoxRoute');

    Route::get('/sync/escuelas', 'SyncSchoolController@store');

    Route::get('/sync/escuelas/comunidad', 'SyncGroupComunnityController@syncSchool');
    Route::get('/sync/grados/comunidad', 'SyncGroupComunnityController@syncGroupGrade');

    Route::post('sync/usuario/comunidad/', 'SyncUserPlatformController@syncUserCommunity');
    Route::post('sync/usuario/academia/', 'SyncUserPlatformController@syncUserAcademy');

    Route::post('sync/activitypoints/', 'SyncUserActivityPoint@SyncUsers');

    Route::post('enroll/usuario/grupo_a/', 'UserThinkificController@groupAssign');

    Route::post('update/role', 'SyncUserPlatformController@updateRole');

    //File upload, download
    Route::post('upload-file', 'ActivityController@fileUpload');
    Route::post('download-file', 'ActivityController@downloadFile');
    Route::delete('remove-file/{name}/{file}', 'ActivityController@removeFile');
    //Firebase sessions
    Route::post('firebase', 'FirebaseController@index');
    //store order
    Route::post('storeOrder', 'UserSubscriptionController@storeOrder');


    Route::get('dashboard', 'DashboardController@index');
    Route::get('dashboard/panel', 'DashboardController@getPanel');
    Route::get('dashboard/phpfox', 'DashboardController@getPhpfox');
    Route::get('dashboard/thinkific', 'DashboardController@getThinkific');
    Route::post('dashboard/homework', 'DashboardController@dashboardHomework');
    Route::get('dashboard/info', 'DashboardController@infoDashbord');
    Route::get('dashboard/notificaciones', 'DashboardController@getForProfileBlock');

    Route::get('lista/membresia', 'OrderController@orderList');
    Route::delete('cancelSubscription/{id}', 'UserSubscriptionController@cancelSubscription');
    Route::get('tipoMembresia', 'LicenseTypeController@tipoMembresia');

    Route::get('google/token', 'CalendarAPIController@getToken');
    Route::get('google/calendars', 'CalendarAPIController@getCalendars');
    Route::get('google/calendars/alumno', 'CalendarAPIController@getStudentCalendars');
    Route::post('google/calendars', 'CalendarAPIController@store');
    Route::get('google/calendars/events', 'CalendarAPIController@getEvent');
    Route::post('google/calendars/events', 'CalendarAPIController@createEvents');
    Route::put('google/calendars/events', 'CalendarAPIController@updateEvent');
    Route::delete('google/calendars/events', 'CalendarAPIController@deleteEvent');
    Route::get('google/calendars/subjects/{id}', 'CalendarAPIController@getSubjects');
    Route::get('verifyURL', 'CalendarAPIController@verifyURL');

    Route::get('calendar/student/subjects', 'CalendarAPIController@getSubjectCalendar');

    Route::get('aulaVirtual/getMeetId/{group_id}', 'VirtualClassroomController@index');
    Route::get('aulaVirtual/getGroupStudent', 'VirtualClassroomController@getGroupStudent');
    Route::get('aulaVirtual/getGradeSuject/{calendar_id}', 'VirtualClassroomController@getGradeSuject');
    Route::get('aulaVirtual/getNames', 'VirtualClassroomController@getNames');
    Route::post('aulaVirtual/upload', 'VirtualClassroomController@store');
    Route::get('aulaVirtual/getFiles/{id}', 'VirtualClassroomController@show');
    Route::post('aulaVirtual/get-file', 'VirtualClassroomController@getFiles');
    Route::post('aulaVirtual/Deletefiles/{id}', 'VirtualClassroomController@Deletefiles');
    Route::post('aulaVirtual/DeletefileById', 'VirtualClassroomController@DeletefileById');
    Route::post('aulaVirtual/addNonPlannedResources', 'VirtualClassroomController@addNonPlannedResources');
    Route::get('aulaVirtual/getNonPlannedResources', 'VirtualClassroomController@getNonPlannedResources');
    Route::post('aulaVirtual/removenpResources', 'VirtualClassroomController@destroyNonPlannedResources');

    Route::get('avatar', 'AvatarController@index');
    Route::post('avatar/sync', 'AvatarController@syncAvatar');
    Route::put('avatar/{id}', 'AvatarController@update');

    Route::post('materias', 'CustomSubjectController@store');
    Route::get('materias/grupo/{id}', 'CustomSubjectController@show');
    Route::get('materias', 'CustomSubjectController@getSubjects');
    Route::put('materias/{id}', 'CustomSubjectController@edit');
    Route::delete('materias/{id}', 'CustomSubjectController@destroy');

    Route::get('materias/count', 'CustomerSubjectController@subjectsGroup');

    Route::get('filtro', 'DigitalResourcesController@subjectResource');

    Route::get('recursos', 'ResourceController@getResource');
    Route::post('upload/recurso', 'ResourceController@storeResource');

    Route::post('sync/level', 'UserController@syncLevel');

    Route::get('activity/count', 'ActivityController@subjectWeek');
    Route::get('activity/duplicate/{id}', 'ActivityController@duplicateActivity');

    Route::get('resources', 'DigitalResourcesController@index');
    Route::get('filtro', 'DigitalResourcesController@subjectResource');
    Route::get('resources/{id}', 'DigitalResourcesController@show');
    Route::post('resources', 'DigitalResourcesController@store');
    Route::delete('resources/{id}', 'DigitalResourcesController@destroy');
    Route::get('resourcesCategories', 'DigitalResourcesController@getCategories');
    Route::get('example', 'ActivityController@navigate');
    Route::get('resourceSubject', 'DigitalResourcesController@getResources');
    Route::put('resources/{id}', 'DigitalResourcesController@update');

    Route::post('makewish', 'WishlistController@store');
    Route::get('getwishlist', 'WishlistController@show');
    Route::delete('deletewish/{course_id}', 'WishlistController@destroy');

    Route::get('users-validations/get-students-courses', 'UserValidationController@getStudentCourses');
    Route::get('users-validations/get-student-course/{courseId}', 'UserValidationController@getStudentCourseById');
    Route::get('users-validations/get-childs', 'UserValidationController@getChilds');
    Route::post('users-validations/booster-membership', 'UserValidationController@boosterMembership');
    Route::post('users-validations/save-childs', 'UserValidationController@saveChilds');
    Route::post('users-validations/save-booster-membership', 'UserValidationController@saveBoosterMembership');
    Route::post('users-validations/save-course-childs', 'UserValidationController@saveCourseChilds');

    Route::get('profile', 'ProfileController@getProfileInfo');
    Route::post('profileUpdate', 'ProfileController@updateProfile');
    Route::post('statusprofileUpdate', 'ProfileController@statusProfile');
    Route::post('chileInfo', 'ProfileController@getChildInfo');

    Route::post('updateInfo', 'TutorController@updateInfo');
    Route::get('users-subscriptions/get-students', 'UserSubscriptionController@getStudents');
    Route::post('password-change', 'UserController@changePasswordPost');
    /**
     * Update Student Profile
     */
    Route::post('students/update-profile', 'StudentController@updateProfile');
});
