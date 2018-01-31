<?php

namespace App\Http\Controllers\LTI\Data;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use EONConsulting\LaravelLTI\Http\Controllers\LTIBaseController;
use App\Models\Course;
use App\Models\TimelineEvent;
use App\Models\IntegrateTaoResults;
use App\Models\SummaryStudentProgression;
use App\Models\SummaryModuleProgression;

class DashboardDataController extends LTIBaseController {

    /**
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function data_courses() {
        $result = Course::all();

        return response()->json($result);
    }

    /**
     * 
     * @param int $course_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function data_students($course_id) {

        $result = [];
        $users = new \EONConsulting\Core\Classes\Users();

        // we need to query the integrate_* tables
        // to find out which students belong to which courses
        // and which students belong to which mentors
        // different user lists for instructors and mentors
        if (laravel_lti()->is_instructor(auth()->user())) {
            $result = $users->GetUsersForCourse($course_id);
        } else if (laravel_lti()->is_mentor(auth()->user())) {
            $result = $users->GetUsersForCourse($course_id, auth()->user()->id);
        }

        return response()->json($result);
    }

    /**
     * 
     * @param int $course_id
     * @param int $student_id
     * @param int $assessment
     * @return \Illuminate\Http\JsonResponse
     */
    public function data_assessment_types($course_id, $student_id, $assessment) {

        // this is the different assessments that a student
        // has participated in
        $analytics = new \EONConsulting\Core\Classes\Analytics();

        // switch assessment
        $result = [];
        switch ($assessment) {
            case "FA": // formative assessments
                $result = $analytics->getAllFormativeAssessments($course_id, $student_id);
                break;
            case "SA": // summative assessments
                $result = $analytics->getAllSummativeAssessments($course_id, $student_id);
                break;
        }

        return response()->json($result);
    }

    /**
     * 
     * @param int $course_id
     * @param int $student_id
     * @param int $assessment
     * @return \Illuminate\Http\JsonResponse
     */
    public function data_assessment_results($course_id, $student_id, $assessment_type) {

        // this is the different assessments that a student
        // has participated in
        $analytics = new \EONConsulting\Core\Classes\Analytics();

        $result = $analytics->getAssessmentResults($course_id, $student_id, $assessment_type);

        return response()->json($result);
    }

    /**
     * 
     * @param int $course_id
     * @param int $student_id
     * @return \Illuminate\Http\JsonResponse
     */
    public function data_progression($course_id, $student_id) {
        if ($student_id == "ALL") {
            // this is the expected progress for the course
            // as well as the class average, pre-calculated
            $smp_items = SummaryModuleProgression::where("course_id", $course_id)
                    ->firstOrFail();

            //$ssp_avg = SummaryStudentProgression::where("course_id", $course_id)
            //        ->avg("progress");


            $result = [
                "course_id" => $course_id,
                "student_id" => $student_id,
                "progress" => [
                    "storyline" => [
                        "class_progress" => [
                            $smp_items["class_progress"]
                        ],
                        "module_progress" => [
                            $smp_items["module_progress"]
                        ],
                        "my_progress" => [
                            0
                        //(int)$ssp_avg
                        ]
                    ]
                ]
            ];
        } else {
            // this is the expected progress for the course
            // as well as the class average, pre-calculated
            $smp_items = SummaryModuleProgression::where("course_id", $course_id)
                    ->firstOrFail();

            $ssp_items = SummaryStudentProgression::where("course_id", $course_id)
                    ->where("student_user_id", $student_id)
                    ->firstOrFail();

            $result = [
                "course_id" => $course_id,
                "student_id" => $student_id,
                "progress" => [
                    "storyline" => [
                        "class_progress" => [
                            $smp_items["class_progress"]
                        ],
                        "module_progress" => [
                            $smp_items["module_progress"]
                        ],
                        "my_progress" => [
                            $ssp_items["progress"]
                        ]
                    ]
                ]
            ];
        }


        return response()->json($result);
    }
    
      /**
     * 
     * @param \Illuminate\Http\Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function filter_timeline_events(Request $request) {
        
        $start = $request->input("start", date('Y-m-d'));
        $end = $request->input("end", date('Y-m-d'));
        $course_id = $request->input("course_id", 0);
        $user_id = auth()->user()->id;
        $role = laravel_lti()->get_user_lti_type(auth()->user());
            
        $timelines = new \EONConsulting\Core\Classes\Timelines();
        $events = $timelines->findByFilters($start, $end, $course_id, $user_id, $role);

        return response()->json($events);
    }
    
    /**
     * @param \Illuminate\Http\Request
     * @return HttpStatusCode
     */
    public function store_timeline_event(Request $request)
    {
        if (is_array($request->all()))
        {
            $data = $request->all();
            
            // only lecturers can set is_global = 1
            $role = laravel_lti()->get_user_lti_type(auth()->user());
            $is_global = ($role == "Instructor") ? $data["is_global"] : 0;
            
            $new_event = [
                "id" => $data["id"],
                "start" => $data["start"],
                "end" => $data["end"],
                "user_id" => auth()->user()->id,
                "course_id" => $data["course_id"],
                "is_global" => $is_global,
                "title" => $data["title"],
                "type" => $data["type"]
                //"url" => $data["url"]
            ];
            
            if ($new_event["id"] > 0)
            {
                // update
                $record = TimelineEvent::find($new_event["id"]);
                
                $record->start = $new_event["start"];
                $record->end = $new_event["end"];
                $record->user_id = $new_event["user_id"];
                $record->course_id = $new_event["course_id"];
                $record->is_global = $new_event["is_global"];
                $record->title = $new_event["title"];
                $record->type = $new_event["type"];
                
                $record->save();
            } else {
                // insert
                $record = TimelineEvent::create($new_event);
            }
            
            if ($record)
            {
                return response('Created', 201);
            } else {
                return response('Server Error', 500);
            }
        } else {
            return response('Bad Request', 400);
        }
    }

}
