<?php

namespace App\Http\Controllers;

use App\GroupModels\Group;
use App\SyncGroupComunnity;
use App\SyncModels\GroupUserEnrollment;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\UserThinkific;
use App\Activity;
use App\Homework;
use Carbon\Carbon;
use App\Http\Controllers\ActivityController;
use phpDocumentor\Fileset\Collection;

class DashboardController extends ApiController
{

    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function getPanel()
    {
        //
        $user = Auth::user();
        $request = request()->all();
        $dashboard = new \stdClass();

        try {

            $dashboard->homeworks = Homework::select('homework.*', 'activity.*', 'custom_subjects.*', \DB::raw('CONCAT(COALESCE(users.name,"")," ",COALESCE(users.second_name+" ",""),COALESCE(users.last_name,"")) as teachers_name'), 'homework.id')
                ->where('student_id', $user->id)
                ->join('activity', 'homework.activity_id', '=', 'activity.id')
                ->join('users', 'activity.teacher_id', '=', 'users.id')
                ->join('custom_subjects', 'activity.subject_id', '=', 'custom_subjects.id')
                ->get();


            if (array_key_exists('today', $request) && $request['today'] !== null) {
                $dashboard->dueWeek = Homework::selectRaw('COUNT(*) AS total')
                    ->where([['homework.student_id', $user->id], ['activity.finish_date', '>', Carbon::parse($request['today'])], ['activity.finish_date', '<', Carbon::parse($request['today'])->addDays(7)]])
                    ->join('activity', 'homework.activity_id', '=', 'activity.id')
                    ->get();
            }

            $dashboard->pending = Homework::select('homework.*', 'activity.*', 'custom_subjects.*', \DB::raw('CONCAT(COALESCE(users.name,"")," ",COALESCE(users.second_name+" ",""),COALESCE(users.last_name,"")) as teachers_name'), 'homework.id')
                ->where([['student_id', $user->id], ['status', 'No entregado']])
                ->join('activity', 'homework.activity_id', '=', 'activity.id')
                ->join('users', 'activity.teacher_id', '=', 'users.id')
                ->join('custom_subjects', 'activity.subject_id', '=', 'custom_subjects.id')
                ->get();

            $dashboard->graded = Homework::select('homework.*', 'activity.*', 'custom_subjects.*', \DB::raw('CONCAT(COALESCE(users.name,"")," ",COALESCE(users.second_name+" ",""),COALESCE(users.last_name,"")) as teachers_name'), 'homework.id')
                ->where([['student_id', $user->id], ['status', 'Calificado']])
                ->join('activity', 'homework.activity_id', '=', 'activity.id')
                ->join('users', 'activity.teacher_id', '=', 'users.id')
                ->join('custom_subjects', 'activity.subject_id', '=', 'custom_subjects.id')
                ->get();

            $dashboard->score = Homework::select('activity.subject_id', 'custom_subjects.custom_name AS name')
                ->selectRaw('COUNT(activity.subject_id) AS total')
                ->selectRaw('SUM(homework.score) / COUNT(homework.score) AS calificacion')
                ->where('homework.student_id', $user->id)
                ->join('activity', 'homework.activity_id', '=', 'activity.id')
                ->join('custom_subjects', 'activity.subject_id', '=', 'custom_subjects.id')
                ->groupBy('activity.subject_id', 'custom_subjects.custom_name')
                ->get();

            return $this->successResponse($dashboard, 200);
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('No se pudo recuperar la información', 422);
        }

    }

    public function getPhpfox(Request $request)
    {
        //
        $user = Auth::user();
        $input = $request->all();
        $dashboard = new \stdClass();

        try {

            /*$groups = \DB::connection('mysql2')
                ->table('phpfox_like')
                ->select('item_id')
                ->where([['user_id', $user->active_phpfox], ['type_id', 'groups']])
                ->pluck('item_id')
                ->toArray();*/

            if ($request->has('groupId')) {

                $group = Group::select('groups.id AS groupId', 'groups.name','schools.name AS schoolName', 'schools.id')
                    ->join('schools', 'schools.id' ,'=', 'groups.school_id')
                    ->where('groups.id', $input->groupId)->firstOrFail();

                $groupP = SyncGroupComunnity::select('page_id', 'user_id', 'title')
                    ->where([['title', '=', $group->schoolName . '-' .$group->name]])->firstOrfail();

            } else {
                $group = Group::select('groups.id AS groupId', 'groups.name','schools.name AS schoolName', 'schools.id')
                    ->join('schools', 'schools.id' ,'=', 'groups.school_id')
                    ->where('groups.teacher_id', $user->id)->firstOrFail();

                $groupP = SyncGroupComunnity::select('page_id', 'user_id', 'title')
                    ->where([['title', '=', $group->schoolName . '-' .$group->name]])->firstOrfail();

            }

            $groupsFeeds = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_pages_feed_comment.content AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->where('phpfox_pages_feed.parent_user_id', $groupP->page_id)
                ->where('phpfox_pages_feed.type_id', 'groups_comment')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_pages_feed_comment', 'phpfox_pages_feed.item_id', '=', 'phpfox_pages_feed_comment.feed_comment_id')
                ->join('phpfox_user', 'phpfox_pages_feed.user_id', '=', 'phpfox_user.user_id')
                ->join('phpfox_pages', 'phpfox_pages_feed.parent_user_id', '=', 'phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(3)
                ->get();

            foreach ($groupsFeeds as $groupsFeed) {
                $groupsFeed->type = 'text';
            }

            $groupsPhotos = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_photo_info.description AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->where('phpfox_pages_feed.parent_user_id', $groupP->page_id)
                ->where('phpfox_pages_feed.type_id', 'photo')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_photo', 'phpfox_pages_feed.item_id', '=', 'phpfox_photo.photo_id')
                ->join('phpfox_user', 'phpfox_pages_feed.user_id', '=', 'phpfox_user.user_id')
                ->join('phpfox_pages', 'phpfox_pages_feed.parent_user_id', '=', 'phpfox_pages.page_id')
                ->join('phpfox_photo_info', 'phpfox_photo.photo_id', '=', 'phpfox_photo_info.photo_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(3)
                ->get();

            foreach ($groupsPhotos as $groupsPhoto) {
                $groupsPhoto->type = 'photo';
            }

            $groupsLinks = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_link.status_info AS content', 'phpfox_link.title AS link_title', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->where('phpfox_pages_feed.parent_user_id', $groupP->page_id)
                ->where('phpfox_pages_feed.type_id', 'link')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_link', 'phpfox_pages_feed.item_id', '=', 'phpfox_link.link_id')
                ->join('phpfox_user', 'phpfox_pages_feed.user_id', '=', 'phpfox_user.user_id')
                ->join('phpfox_pages', 'phpfox_pages_feed.parent_user_id', '=', 'phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(3)
                ->get();

            foreach ($groupsLinks as $groupsLink) {
                $groupsLink->type = 'link';
            }

            $groupsVideos = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_video.status_info AS content', 'phpfox_video.title AS video_title', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->where('phpfox_pages_feed.parent_user_id', $groupP->page_id)
                ->where('phpfox_pages_feed.type_id', 'v')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->where('phpfox_pages.title', '!=', 'LIA-Alumnos LIA')
                ->join('phpfox_video', 'phpfox_pages_feed.item_id', '=', 'phpfox_video.video_id')
                ->join('phpfox_user', 'phpfox_pages_feed.user_id', '=', 'phpfox_user.user_id')
                ->join('phpfox_pages', 'phpfox_pages_feed.parent_user_id', '=', 'phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(3)
                ->get();

            foreach ($groupsVideos as $groupsVideo) {
                $groupsVideo->type = 'video';
            }

            $groupsPolls = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_poll.question AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->where('phpfox_pages_feed.parent_user_id', $groupP->page_id)
                ->where('phpfox_pages_feed.type_id', 'poll')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_poll', 'phpfox_pages_feed.item_id', '=', 'phpfox_poll.poll_id')
                ->join('phpfox_user', 'phpfox_pages_feed.user_id', '=', 'phpfox_user.user_id')
                ->join('phpfox_pages', 'phpfox_pages_feed.parent_user_id', '=', 'phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(3)
                ->get();

            foreach ($groupsPolls as $groupsPoll) {
                $groupsPoll->type = 'poll';
            }

            $groupsBlogs = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_blog.title AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->where('phpfox_pages_feed.parent_user_id', $groupP->page_id)
                ->where('phpfox_pages_feed.type_id', 'blog')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_blog', 'phpfox_pages_feed.item_id', '=', 'phpfox_blog.blog_id')
                ->join('phpfox_user', 'phpfox_pages_feed.user_id', '=', 'phpfox_user.user_id')
                ->join('phpfox_pages', 'phpfox_pages_feed.parent_user_id', '=', 'phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(3)
                ->get();

            foreach ($groupsBlogs as $groupsBlog) {
                $groupsBlog->type = 'blog';
            }

            $groupsEvents = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_event.title AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->where('phpfox_pages_feed.parent_user_id', $groupP->page_id)
                ->where('phpfox_pages_feed.type_id', 'event')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_event', 'phpfox_pages_feed.item_id', '=', 'phpfox_event.event_id')
                ->join('phpfox_user', 'phpfox_pages_feed.user_id', '=', 'phpfox_user.user_id')
                ->join('phpfox_pages', 'phpfox_pages_feed.parent_user_id', '=', 'phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(3)
                ->get();

            foreach ($groupsEvents as $groupsEvent) {
                $groupsEvent->type = 'event';
            }

            $groupsForums = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_forum_post.title AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->where('phpfox_pages_feed.parent_user_id', $groupP->page_id)
                ->where('phpfox_pages_feed.type_id', 'forum')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_forum_post', 'phpfox_pages_feed.item_id', '=', 'phpfox_forum_post.post_id')
                ->join('phpfox_user', 'phpfox_pages_feed.user_id', '=', 'phpfox_user.user_id')
                ->join('phpfox_pages', 'phpfox_pages_feed.parent_user_id', '=', 'phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(3)
                ->get();

            foreach ($groupsForums as $groupsForum) {
                $groupsForum->type = 'forum';
            }

            $groupsListings = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_marketplace.title AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->where('phpfox_pages_feed.parent_user_id', $groupP->page_id)
                ->where('phpfox_pages_feed.type_id', 'marketplace')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_marketplace', 'phpfox_pages_feed.item_id', '=', 'phpfox_marketplace.listing_id')
                ->join('phpfox_user', 'phpfox_pages_feed.user_id', '=', 'phpfox_user.user_id')
                ->join('phpfox_pages', 'phpfox_pages_feed.parent_user_id', '=', 'phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(3)
                ->get();

            foreach ($groupsListings as $groupsListing) {
                $groupsListing->type = 'market';
            }

            $groupsSongs = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_music_song.title AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->where('phpfox_pages_feed.parent_user_id', $groupP->page_id)
                ->where('phpfox_pages_feed.type_id', 'music_song')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->where('phpfox_pages.title', '!=', 'LIA-Alumnos LIA')
                ->join('phpfox_music_song', 'phpfox_pages_feed.item_id', '=', 'phpfox_music_song.song_id')
                ->join('phpfox_user', 'phpfox_pages_feed.user_id', '=', 'phpfox_user.user_id')
                ->join('phpfox_pages', 'phpfox_pages_feed.parent_user_id', '=', 'phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(3)
                ->get();

            foreach ($groupsSongs as $groupsSong) {
                $groupsSong->type = 'song';
            }

            $groupsAlbums = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_music_album.name AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->where('phpfox_pages_feed.parent_user_id', $groupP->page_id)
                ->where('phpfox_pages_feed.type_id', 'music_album')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->where('phpfox_pages.title', '!=', 'LIA-Alumnos LIA')
                ->join('phpfox_music_album', 'phpfox_pages_feed.item_id', '=', 'phpfox_music_album.album_id')
                ->join('phpfox_user', 'phpfox_pages_feed.user_id', '=', 'phpfox_user.user_id')
                ->join('phpfox_pages', 'phpfox_pages_feed.parent_user_id', '=', 'phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(3)
                ->get();

            foreach ($groupsAlbums as $groupsAlbum) {
                $groupsAlbum->type = 'album';
            }

            $groupsQuizzes = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_quiz.title AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->where('phpfox_pages_feed.parent_user_id', $groupP->page_id)
                ->where('phpfox_pages_feed.type_id', 'quiz')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_quiz', 'phpfox_pages_feed.item_id', '=', 'phpfox_quiz.quiz_id')
                ->join('phpfox_user', 'phpfox_pages_feed.user_id', '=', 'phpfox_user.user_id')
                ->join('phpfox_pages', 'phpfox_pages_feed.parent_user_id', '=', 'phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(3)
                ->get();

            foreach ($groupsQuizzes as $groupsQuiz) {
                $groupsQuiz->type = 'quiz';
            }

            $feeds = collect();

            $feeds = $feeds->merge($groupsFeeds);
            $feeds = $feeds->merge($groupsLinks);
            $feeds = $feeds->merge($groupsPhotos);
            $feeds = $feeds->merge($groupsVideos);
            $feeds = $feeds->merge($groupsPolls);
            $feeds = $feeds->merge($groupsBlogs);
            $feeds = $feeds->merge($groupsEvents);
            $feeds = $feeds->merge($groupsForums);
            $feeds = $feeds->merge($groupsListings);
            $feeds = $feeds->merge($groupsSongs);
            $feeds = $feeds->merge($groupsAlbums);
            $feeds = $feeds->merge($groupsQuizzes);


            $feeds = $feeds->sortByDesc(function ($feed) {
                return $feed->time_stamp;
            })->values()->take(3)->all();

            foreach ($feeds as $feed) {
                $feed->time_stamp = Carbon::parse($feed->time_stamp)->format('j M  H:i');
            }

            $dashboard->feed = $feeds;

            $podcastSongs = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_music_song.title AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->where('phpfox_pages.title', 'LIA-Alumnos LIA')
                ->where('phpfox_pages_feed.type_id', 'music_song')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_music_song', 'phpfox_pages_feed.item_id', '=', 'phpfox_music_song.song_id')
                ->join('phpfox_user', 'phpfox_pages_feed.user_id', '=', 'phpfox_user.user_id')
                ->join('phpfox_pages', 'phpfox_pages_feed.parent_user_id', '=', 'phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(3)
                ->get();

            foreach ($podcastSongs as $song) {
                $song->type = 'song';
            }

            $podcastAlbums = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_music_album.name AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->where('phpfox_pages.title', 'LIA-Alumnos LIA')
                ->where('phpfox_pages_feed.type_id', 'music_album')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_music_album', 'phpfox_pages_feed.item_id', '=', 'phpfox_music_album.album_id')
                ->join('phpfox_user', 'phpfox_pages_feed.user_id', '=', 'phpfox_user.user_id')
                ->join('phpfox_pages', 'phpfox_pages_feed.parent_user_id', '=', 'phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(3)
                ->get();

            foreach ($podcastAlbums as $album) {
                $album->type = 'album';
            }

            $podcasts = collect();

            $podcasts = $podcasts->merge($podcastSongs);
            $podcasts = $podcasts->merge($podcastAlbums);

            $podcasts = $podcasts->sortByDesc(function ($podcast) {
                return $podcast->time_stamp;
            })->values()->take(3)->all();

            foreach ($podcasts as $podcast) {
                $podcast->time_stamp = Carbon::parse($podcast->time_stamp)->format('j M  H:i');
            }

            $dashboard->podcasts = $podcasts;

            $dashboard->videos = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_video.status_info AS content', 'phpfox_video.title AS video_title', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->where('phpfox_pages.title', 'LIA-Alumnos LIA')
                ->where('phpfox_pages_feed.type_id', 'v')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_video', 'phpfox_pages_feed.item_id', '=', 'phpfox_video.video_id')
                ->join('phpfox_user', 'phpfox_pages_feed.user_id', '=', 'phpfox_user.user_id')
                ->join('phpfox_pages', 'phpfox_pages_feed.parent_user_id', '=', 'phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(3)
                ->get();

            foreach ($dashboard->videos as $video) {
                $video->type = 'video';
                $video->time_stamp = Carbon::parse($video->time_stamp)->format('j M  H:i');
            }

            $dashboard->points = \DB::connection('mysql2')
                ->table('phpfox_activitypoint_statistics')
                ->select('*')
                ->selectRaw('((total_earned + total_bought + total_received) - (total_sent + total_spent + total_retrieved)) AS current')
                ->where('user_id', $user->active_phpfox)->get();

            if ($dashboard->points->isEmpty()) {
                $dashboard->points['user_id'] = $user->active_phpfox;
                $dashboard->points['total_earned'] = 0;
                $dashboard->points['total_bought'] = 0;
                $dashboard->points['total_sent'] = 0;
                $dashboard->points['total_spent'] = 0;
                $dashboard->points['total_received'] = 0;
                $dashboard->points['total_retrieved'] = 0;
                $dashboard->points['current'] = 0;
            } else {
                $dashboard->points = $dashboard->points[0];
            }

            return $this->successResponse($dashboard, 'Información del dashbord',200);

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Hubo un problema al consultar la información', 422);
        }
    }

    /**
     * Display a listing of the resource.
     */
    public function getThinkific()
    {
        try {

            $user = Auth::user();

            $userThink = new UserThinkific();
            $data = [
                'query[user_id]' => $user->active_thinkific,
                'query[completed]' => false
            ];

            $enrollments = $userThink->userEnrollments($data);
            $courses = $userThink->coursesAvaible();

            $infoT = collect([['enrollments' => $enrollments], ['courses ' => $courses]]);

            return $infoT;

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('No se pudo recuperar la información', 422);
        }
    } 

    /**
     * Display info about homeworks.
     */
    public function dashboardHomework(Request $request)
    {
        $user = Auth::user();
        $dashboardH = new \stdClass();

        try {

            if ($request->has('idGroup')){
                $input = $request->all();
                $idGroup = $input['idGroup'];
                $dashboardH->lastHomeworks = Activity::select('activity.name')
                ->selectRaw('COUNT(case when homework.status = "No entregado" and homework.is_active != "2" then homework.id end) AS NoEntregado')
                ->selectRaw('COUNT(case when homework.status = "Entregado" then homework.id end) AS Entregado')
                ->where([['activity.teacher_id', $user->id], ['activity.group_id', $idGroup]])
                ->leftjoin('homework', 'activity.id', '=', 'homework.activity_id')
                ->groupBy('activity.id')
                ->orderBy('activity.id', 'desc')
                ->get();

                $dashboardH->topHomeworks = \DB::table('users')
                ->join('homework', 'users.id', '=', 'homework.student_id')
                ->join('group_user_enrollments', 'group_user_enrollments.user_id', '=', 'users.id')
                ->join('groups', 'groups.id', '=', 'group_user_enrollments.group_id')
                ->leftJoin('avatar_users', 'users.id', 'avatar_users.user_id')
                ->select('users.id' ,'users.name', 'users.second_name', 'users.last_name', 'users.second_last_name', 'avatar_users.avatar_path as avatar', 'groups.name AS group',
                    DB::raw('CONCAT(COALESCE(users.name, ""), " ", COALESCE(users.second_name, " "), " ", COALESCE(users.last_name, " "), " ", COALESCE(users.second_last_name, " ")) AS full_name'),
                    DB::raw('sum(homework.score) as score'),
                    DB::raw('AVG(score) as promedio'))
                ->selectRaw('COUNT(case when homework.status = "No entregado" and homework.is_active != "2" then homework.id end) AS NoEntregado')
                ->selectRaw('COUNT(case when homework.status = "Entregado" then homework.id end) AS Entregado')
                ->where([['groups.teacher_id', $user->id], ['groups.id', $idGroup]])
                ->groupBy('users.id')
                ->orderBy('promedio', 'asc')
                ->limit(5)
                ->get();

                $dashboardH->downHomeworks = \DB::table('users')
                ->join('homework', 'users.id', '=', 'homework.student_id')
                ->join('group_user_enrollments', 'group_user_enrollments.user_id', '=', 'users.id')
                ->join('groups', 'groups.id', '=', 'group_user_enrollments.group_id')
                ->leftJoin('avatar_users', 'users.id', 'avatar_users.user_id')
                ->select('users.id' ,'users.name', 'users.second_name', 'users.last_name', 'users.second_last_name', 'avatar_users.avatar_path as avatar', 'groups.name AS group',
                    DB::raw('CONCAT(COALESCE(users.name, ""), " ", COALESCE(users.second_name, " "), " ", COALESCE(users.last_name, " "), " ", COALESCE(users.second_last_name, " ")) AS full_name'),
                    DB::raw('sum(homework.score) as score'),
                    DB::raw('AVG(score) as promedio'))
                ->selectRaw('COUNT(case when homework.status = "No entregado" and homework.is_active != "2" then homework.id end) AS NoEntregado')
                ->selectRaw('COUNT(case when homework.status = "Entregado" then homework.id end) AS Entregado')
                ->where([['groups.teacher_id', $user->id], ['groups.id', $idGroup]])
                ->groupBy('users.id')
                ->orderBy('promedio', 'desc')
                ->limit(5)
                ->get();
            }
            else {
                $dashboardH->lastHomeworks = Activity::select('activity.name')
                ->selectRaw('COUNT(case when homework.status = "No entregado" and homework.is_active != "2" then homework.id end) AS NoEntregado')
                ->selectRaw('COUNT(case when homework.status = "Entregado" then homework.id end) AS Entregado')
                ->where('activity.teacher_id', '=', $user->id)
                ->leftjoin('homework', 'activity.id', '=', 'homework.activity_id')
                ->groupBy('activity.id')
                ->orderBy('activity.id', 'desc')
                ->get();

                $dashboardH->topHomeworks = DB::table('users')
                ->join('homework', 'users.id', '=', 'homework.student_id')
                ->join('group_user_enrollments', 'group_user_enrollments.user_id', '=', 'users.id')
                ->join('groups', 'groups.id', '=', 'group_user_enrollments.group_id')
                ->leftJoin('avatar_users', 'users.id', 'avatar_users.user_id')
                ->select('users.id' ,'users.name', 'users.second_name', 'users.last_name', 'users.second_last_name', 'avatar_users.avatar_path as avatar', 'groups.name AS group',
                    \DB::raw('CONCAT(COALESCE(users.name, ""), " ", COALESCE(users.second_name, " "), " ", COALESCE(users.last_name, " "), " ", COALESCE(users.second_last_name, " ")) AS full_name'),
                    \DB::raw('sum(homework.score) as score'),
                    \DB::raw('AVG(score) as promedio'))
                ->selectRaw('COUNT(case when homework.status = "No entregado" and homework.is_active != "2" then homework.id end) AS NoEntregado')
                ->selectRaw('COUNT(case when homework.status = "Entregado" then homework.id end) AS Entregado')
                ->where('groups.teacher_id', '=', $user->id)
                ->groupBy('users.id')
                ->orderBy('promedio', 'asc')
                ->limit(5)
                ->get();

                $dashboardH->downHomeworks = DB::table('users')
                ->join('homework', 'users.id', '=', 'homework.student_id')
                ->join('group_user_enrollments', 'group_user_enrollments.user_id', '=', 'users.id')
                ->join('groups', 'groups.id', '=', 'group_user_enrollments.group_id')
                ->leftJoin('avatar_users', 'users.id', 'avatar_users.user_id')
                ->select('users.id' ,'users.name', 'users.second_name', 'users.last_name', 'users.second_last_name', 'avatar_users.avatar_path as avatar', 'groups.name AS group',
                    DB::raw('CONCAT(COALESCE(users.name, ""), " ", COALESCE(users.second_name, " "), " ", COALESCE(users.last_name, " "), " ", COALESCE(users.second_last_name, " ")) AS full_name'),
                    DB::raw('sum(homework.score) as score'),
                    DB::raw('AVG(score) as promedio'))
                ->selectRaw('COUNT(case when homework.status = "No entregado" and homework.is_active != "2" then homework.id end) AS NoEntregado')
                ->selectRaw('COUNT(case when homework.status = "Entregado" then homework.id end) AS Entregado')
                ->where('groups.teacher_id', '=', $user->id)
                ->groupBy('users.id')
                ->orderBy('promedio', 'desc')
                ->limit(5)
                ->get();
            }

            foreach($dashboardH->downHomeworks as $student){
                $promedio = Homework::join('activity', 'homework.activity_id', 'activity.id' )
                    ->join('users', 'homework.student_id', 'users.id')
                    ->join('group_user_enrollments', 'group_user_enrollments.user_id', 'users.id')
                    ->select(DB::raw('AVG(score) as promedio'))
                    ->where([['activity.teacher_id', '=', $user->id], ['group_user_enrollments.user_id', '=', $student->id],['activity.is_active', '=', '1']])
                    ->get()->toArray();

                $student->promedio = $promedio[0]['promedio'];
            }
            foreach($dashboardH->topHomeworks as $student){
                $promedio = Homework::join('activity', 'homework.activity_id', 'activity.id' )
                    ->join('users', 'homework.student_id', 'users.id')
                    ->join('group_user_enrollments', 'group_user_enrollments.user_id', 'users.id')
                    ->select(DB::raw('AVG(score) as promedio'))
                    ->where([['activity.teacher_id', '=', $user->id], ['group_user_enrollments.user_id', '=', $student->id],['activity.is_active', '=', '1']])
                    ->get()->toArray();

                $student->promedio = $promedio[0]['promedio'];
            }

            return $dashboardH;

        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('No se pudo recuperar la información', 422);
        }

    }

    public function infoDashbord(Request $request){

        $activities = self::dashboardHomework($request);
        $courses = self::getThinkific();
        $comunidad = self::getForProfileBlock();

        $collection = collect([['actividades' => $activities],['lia-u' => $courses], ['mundolia' => $comunidad] ]);

        return $this->successResponse($collection, 'Infomación del Dashbord', 200);
    }

    public function getForProfileBlock()
    {
        try {

        $user = Auth::user();

        $iLimit = 4;

        $notificaciones = DB::connection('mysql2')
            ->table('phpfox_notification')
            ->where([['user_id', '=' , $user->active_phpfox] , ['is_seen', '=', 0]])
            ->get()
            ->count();

        $solicitudes = DB::connection('mysql2')
            ->table('phpfox_friend_request')
            ->where([['user_id', '=', $user->active_phpfox],['is_seen', '=', 0], ['is_ignore', '=', 0]])
            ->get()
            ->count();

        $events = DB::connection('mysql2')
            ->table('phpfox_event')
            ->join('phpfox_event_invite', 'phpfox_event_invite.event_id', '=',  'phpfox_event.event_id')
            ->where('phpfox_event.start_time', '>=', Carbon::now()->timestamp)
            ->select("phpfox_event.event_id", 'phpfox_event.title',"phpfox_event.start_time", 'phpfox_event.end_time', 'phpfox_event.image_path')
            ->limit($iLimit)
            ->get();

            $groups = \DB::connection('mysql2')
                ->table('phpfox_like')
                ->select('item_id')
                ->where([['user_id', $user->active_phpfox], ['type_id', 'groups']])
                ->pluck('item_id')
                ->toArray();

            $groupsFeeds = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_pages_feed_comment.content AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->whereIn('phpfox_pages_feed.parent_user_id', $groups)
                ->where('phpfox_pages_feed.type_id', 'groups_comment')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_pages_feed_comment','phpfox_pages_feed.item_id','=','phpfox_pages_feed_comment.feed_comment_id')
                ->join('phpfox_user','phpfox_pages_feed.user_id','=','phpfox_user.user_id')
                ->join('phpfox_pages','phpfox_pages_feed.parent_user_id','=','phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(5)
                ->get();

            foreach ($groupsFeeds as $groupsFeed) {
                $groupsFeed->type = 'text';
            }

            $groupsPhotos = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_photo_info.description AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->whereIn('phpfox_pages_feed.parent_user_id', $groups)
                ->where('phpfox_pages_feed.type_id', 'photo')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_photo','phpfox_pages_feed.item_id','=','phpfox_photo.photo_id')
                ->join('phpfox_user','phpfox_pages_feed.user_id','=','phpfox_user.user_id')
                ->join('phpfox_pages','phpfox_pages_feed.parent_user_id','=','phpfox_pages.page_id')
                ->join('phpfox_photo_info','phpfox_photo.photo_id','=','phpfox_photo_info.photo_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(5)
                ->get();

            foreach ($groupsPhotos as $groupsPhoto) {
                $groupsPhoto->type = 'photo';
            }

            $groupsLinks = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_link.status_info AS content', 'phpfox_link.title AS link_title', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->whereIn('phpfox_pages_feed.parent_user_id', $groups)
                ->where('phpfox_pages_feed.type_id', 'link')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_link','phpfox_pages_feed.item_id','=','phpfox_link.link_id')
                ->join('phpfox_user','phpfox_pages_feed.user_id','=','phpfox_user.user_id')
                ->join('phpfox_pages','phpfox_pages_feed.parent_user_id','=','phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(5)
                ->get();

            foreach ($groupsLinks as $groupsLink) {
                $groupsLink->type = 'link';
            }

            $groupsVideos = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_video.status_info AS content', 'phpfox_video.title AS video_title', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->whereIn('phpfox_pages_feed.parent_user_id', $groups)
                ->where('phpfox_pages_feed.type_id', 'v')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->where('phpfox_pages.title', '!=','LIA-Alumnos LIA')
                ->join('phpfox_video','phpfox_pages_feed.item_id','=','phpfox_video.video_id')
                ->join('phpfox_user','phpfox_pages_feed.user_id','=','phpfox_user.user_id')
                ->join('phpfox_pages','phpfox_pages_feed.parent_user_id','=','phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(5)
                ->get();

            foreach ($groupsVideos as $groupsVideo) {
                $groupsVideo->type = 'video';
            }

            $groupsPolls = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_poll.question AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->whereIn('phpfox_pages_feed.parent_user_id', $groups)
                ->where('phpfox_pages_feed.type_id', 'poll')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_poll','phpfox_pages_feed.item_id','=','phpfox_poll.poll_id')
                ->join('phpfox_user','phpfox_pages_feed.user_id','=','phpfox_user.user_id')
                ->join('phpfox_pages','phpfox_pages_feed.parent_user_id','=','phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(5)
                ->get();

            foreach ($groupsPolls as $groupsPoll) {
                $groupsPoll->type = 'poll';
            }

            $groupsBlogs = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_blog.title AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->whereIn('phpfox_pages_feed.parent_user_id', $groups)
                ->where('phpfox_pages_feed.type_id', 'blog')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_blog','phpfox_pages_feed.item_id','=','phpfox_blog.blog_id')
                ->join('phpfox_user','phpfox_pages_feed.user_id','=','phpfox_user.user_id')
                ->join('phpfox_pages','phpfox_pages_feed.parent_user_id','=','phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(5)
                ->get();

            foreach ($groupsBlogs as $groupsBlog) {
                $groupsBlog->type = 'blog';
            }

            $groupsEvents = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_event.title AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->whereIn('phpfox_pages_feed.parent_user_id', $groups)
                ->where('phpfox_pages_feed.type_id', 'event')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_event','phpfox_pages_feed.item_id','=','phpfox_event.event_id')
                ->join('phpfox_user','phpfox_pages_feed.user_id','=','phpfox_user.user_id')
                ->join('phpfox_pages','phpfox_pages_feed.parent_user_id','=','phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(5)
                ->get();

            foreach ($groupsEvents as $groupsEvent) {
                $groupsEvent->type = 'event';
            }

            $groupsForums = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_forum_post.title AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->whereIn('phpfox_pages_feed.parent_user_id', $groups)
                ->where('phpfox_pages_feed.type_id', 'forum')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_forum_post','phpfox_pages_feed.item_id','=','phpfox_forum_post.post_id')
                ->join('phpfox_user','phpfox_pages_feed.user_id','=','phpfox_user.user_id')
                ->join('phpfox_pages','phpfox_pages_feed.parent_user_id','=','phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(5)
                ->get();

            foreach ($groupsForums as $groupsForum) {
                $groupsForum->type = 'forum';
            }

            $groupsListings = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_marketplace.title AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->whereIn('phpfox_pages_feed.parent_user_id', $groups)
                ->where('phpfox_pages_feed.type_id', 'marketplace')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_marketplace','phpfox_pages_feed.item_id','=','phpfox_marketplace.listing_id')
                ->join('phpfox_user','phpfox_pages_feed.user_id','=','phpfox_user.user_id')
                ->join('phpfox_pages','phpfox_pages_feed.parent_user_id','=','phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(5)
                ->get();

            foreach ($groupsListings as $groupsListing) {
                $groupsListing->type = 'market';
            }

            $groupsSongs = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_music_song.title AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->whereIn('phpfox_pages_feed.parent_user_id', $groups)
                ->where('phpfox_pages_feed.type_id', 'music_song')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->where('phpfox_pages.title', '!=','LIA-Alumnos LIA')
                ->join('phpfox_music_song','phpfox_pages_feed.item_id','=','phpfox_music_song.song_id')
                ->join('phpfox_user','phpfox_pages_feed.user_id','=','phpfox_user.user_id')
                ->join('phpfox_pages','phpfox_pages_feed.parent_user_id','=','phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(5)
                ->get();

            foreach ($groupsSongs as $groupsSong) {
                $groupsSong->type = 'song';
            }

            $groupsAlbums = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_music_album.name AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->whereIn('phpfox_pages_feed.parent_user_id', $groups)
                ->where('phpfox_pages_feed.type_id', 'music_album')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->where('phpfox_pages.title', '!=','LIA-Alumnos LIA')
                ->join('phpfox_music_album','phpfox_pages_feed.item_id','=','phpfox_music_album.album_id')
                ->join('phpfox_user','phpfox_pages_feed.user_id','=','phpfox_user.user_id')
                ->join('phpfox_pages','phpfox_pages_feed.parent_user_id','=','phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(5)
                ->get();

            foreach ($groupsAlbums as $groupsAlbum) {
                $groupsAlbum->type = 'album';
            }

            $groupsQuizzes = \DB::connection('mysql2')
                ->table('phpfox_pages_feed')
                ->select('phpfox_quiz.title AS content', 'phpfox_user.full_name', 'phpfox_pages.title', 'phpfox_pages_feed.time_stamp')
                ->whereIn('phpfox_pages_feed.parent_user_id', $groups)
                ->where('phpfox_pages_feed.type_id', 'quiz')
                ->where('phpfox_pages_feed.user_id', '!=', $user->active_phpfox)
                ->join('phpfox_quiz','phpfox_pages_feed.item_id','=','phpfox_quiz.quiz_id')
                ->join('phpfox_user','phpfox_pages_feed.user_id','=','phpfox_user.user_id')
                ->join('phpfox_pages','phpfox_pages_feed.parent_user_id','=','phpfox_pages.page_id')
                ->orderByDesc('phpfox_pages_feed.time_stamp')
                ->limit(5)
                ->get();

            foreach ($groupsQuizzes as $groupsQuiz) {
                $groupsQuiz->type = 'quiz';
            }

            $feeds = collect();

            $feeds = $feeds->merge($groupsFeeds);
            $feeds = $feeds->merge($groupsLinks);
            $feeds = $feeds->merge($groupsPhotos);
            $feeds = $feeds->merge($groupsVideos);
            $feeds = $feeds->merge($groupsPolls);
            $feeds = $feeds->merge($groupsBlogs);
            $feeds = $feeds->merge($groupsEvents);
            $feeds = $feeds->merge($groupsForums);
            $feeds = $feeds->merge($groupsListings);
            $feeds = $feeds->merge($groupsSongs);
            $feeds = $feeds->merge($groupsAlbums);
            $feeds = $feeds->merge($groupsQuizzes);


            $feeds = $feeds->sortByDesc(function($feed){
                return $feed->time_stamp;
            })->values()->take(5)->all();

            foreach ($feeds as $feed) {
                $feed->time_stamp = Carbon::parse($feed->time_stamp)->format('j M  H:i');
            }

            return collect([['notificaciones' => $notificaciones],['solicitudes' => $solicitudes],['eventos' => $events], ['group_feeds' => $feeds]]);

        }catch (ModelNotFoundException $exception){
            return 'No ha sido posible consultar la información';
        }
    }
}
