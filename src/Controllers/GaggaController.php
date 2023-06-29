<?php

declare(strict_types=1);

namespace Engelsystem\Controllers;

use Carbon\Carbon;
use Engelsystem\Http\Exceptions\HttpNotFound;
use Engelsystem\Helpers\Authenticator;
use Engelsystem\Http\Request;
use Engelsystem\Http\Response;
use Engelsystem\Mail\EngelsystemMailer;
use Engelsystem\Models\AngelType;
use Engelsystem\Models\Gagga;
use Engelsystem\Models\User\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Illuminate\Support\Str;
use Nyholm\Psr7\Stream;

function angleArray() {
    $at = AngelType::all();
    $angeltypes = array();
    foreach ($at as $at) {
        $angeltypes[$at->id] = $at->name;
    }
    return $angeltypes;
}

function GaggaForm_edit_view(
    User $user,
    Gagga $form
    ) {
    $angletypes = angleArray();
    return page_with_title("Gagga Helfer Umfrage", [
        msg(),
        form([
            div('row', [
                div('col-md-9', [
                    form_text('first_name', __('Vorname'), $user->personalData->first_name),
                    form_text('last_name', __('Name'), $user->personalData->last_name),
                    form_text('address', __('Addresse'), $form->address),
                    form_text('zip', __('PLZ'), $form->zip),
                    form_text('mobile', __('mobile'), $user->contact->mobile),
                    form_date('birthday', __('Geburtstag'), $form->birthday),
                    form_text('food', __('Essenswünsche'), $form->food),
                    form_checkbox('driver_license', __('Fahrerlaubnis'), $form->driver_license == 1 ? 'selected' : ''),

                    form_datetime('planned_arrival_date', __('Ankunft'), $user->personalData->planned_arrival_date),
                    form_datetime('planned_departure_date', __('Abreise'), $user->personalData->planned_departure_date),

                    form_select("preferred_type", __('Beforzugter Engeltyp'), $angletypes, $user->prefered_type),

                    form_textarea('can_bring', __('Kann mitbringen'), $form->can_bring),
                    form_textarea('my_best_experience', __('Meine Beste Gagga Erfahrung'), $form->my_best_experience),
                    form_textarea('note', __('Notiz'), $form->note),
                ]),
            ]),
            div('row', [
                div('col-md-6', [
                    form_submit('submit', __('Save')),
                ]),
            ]),
        ]),
    ]);
}

class GaggaController extends BaseController
{
    use HasUserNotifications;

    /** @var array<string, string> */
    protected array $permissions = [
        'show' => 'user_settings',
        'list' => 'admin_user',
        'exportCsv' => 'admin_user',
    ];

    public function __construct(
        protected Authenticator $auth,
        protected Response $response,
        protected SessionInterface $session,
        protected EngelsystemMailer $mail,
        protected LoggerInterface $log
    ) {
    }

    public function update(Request $request): Response
    {
        // $data = $this->validate($request, [
        //     'email' => 'required|email',
        // ]);

        $currentUser = $this->auth->user();
        /** @var User $user */
        $model = Gagga::whereUserId($currentUser->id)->first();
        if ($model === null) {
            $model = new Gagga();
        }
        dump($request);
        $data = $this->validate($request, [
            'first_name' => 'required|optional',
            'last_name' => 'required|optional',
            'address' => 'required|optional',
            'mobile' => 'required|optional',
            'zip' => 'required|optional|int',
            'birthday' => 'required|optional',
            'food' => 'required|optional',
            'preferred_type' => 'required|optional',
            'driver_license' => 'required|optional',
            'planned_arrival_date' => 'required',
            'planned_departure_date' => 'required',
            'can_bring' => 'required|optional',
            'my_best_experience' => 'required|optional',
            'note' => 'required|optional',
        ]);
        dump($data);
        if ($data['driver_license'] == 'checked') {
            $data['driver_license'] = true;
        }
        if ($data['zip'] == '') {
            $data['zip'] = null;
        }
        $model->user_id = $currentUser->id;

        $model->fill($data);
        $model->preferred_type = $data['preferred_type'];
        $model->save();
        $currentUser->personalData->fill($data);
        $currentUser->contact->mobile = $data['mobile'];
        $currentUser->contact->save();
        $currentUser->personalData->save();

        return $this->response->redirectTo("/angeltypes/about");
        //$form = Gagga::whereId(1)->first();
        //$form = [];
        //return $this->show($request);
    }

    public function show(Request $request): Response
    {
        // $data = $this->validate($request, [
        //     'email' => 'required|email',
        // ]);

        $currentUser = $this->auth->user();
        /** @var User $user */
        $data = Gagga::whereUserId($currentUser->id)->first();
        if ($data === null) {
            $data = new Gagga();
        }
        //$form = Gagga::whereId(1)->first();
        //$form = [];
        $form = GaggaForm_edit_view($currentUser, $data);
        //return $this->response->withView($out);

         return $this->response->withView('pages/gagga/form', [
             "form" => $form,
         ]);
    }

    public function list(Request $request): Response
    {

        $order_by = 'name';
        if (
            $request->has('OrderBy') && in_array($request->input('OrderBy'), [
                'name',
                'first_name',
                'last_name',
                'dect',
                'arrived',
                'got_voucher',
                'freeloads',
                'active',
                'force_active',
                'got_shirt',
                'shirt_size',
                'planned_arrival_date',
                'planned_departure_date',
                'last_login_at',
            ])
        ) {
            $order_by = $request->input('OrderBy');
        }

        /** @var User[]|Collection $users */
        $users = User::with(['contact', 'personalData', 'state'])
            ->orderBy('name')
            ->get();
        foreach ($users as $user) {
            $user->setAttribute(
                'freeloads',
                $user->shiftEntries()
                    ->where('freeloaded', true)
                    ->count()
            );
        }

        $gagga = Gagga::with(['user', 'user.contact', 'user.personalData', 'user.state'])
            ->get();

        $users = $users->sortBy(function (User $user) use ($order_by) {
            $userData = $user->toArray();
            $data = [];
            array_walk_recursive($userData, function ($value, $key) use (&$data) {
                $data[$key] = $value;
            });

            return isset($data[$order_by]) ? Str::lower($data[$order_by]) : null;
        });

        return $this->response->withView('pages/gagga/list', [
            "users" => $users,
            "gagga" => $gagga,
            "angletypes" => angleArray(),
        ]);
    }

    public function exportCsv(Request $request)
    {
        $fileName = 'gagga_helfer.csv';
        $forms = Gagga::all();
        $angletypes = angleArray();

        $columns = array('Login', 'First Name', 'Last Name', 'Preferred', 'Address', 'Mobile',
                         'Zip', 'Birthday', 'Food', 'Driver', 'Arrival', 'Departure',
                         'Can Bring', 'Best Experience', 'Note');

        $file = fopen('php://memory', 'w');
        fputcsv($file, $columns);

        foreach ($forms as $form) {
            fputcsv($file, array(
                $form->user->name,
                $form->user->personalData->first_name,
                $form->user->personalData->last_name,
                $angletypes[$form->preferred_type],
                $form->address,
                $form->user->contact->mobile,
                $form->zip,
                $form->birthday,
                $form->food,
                $form->driver_license,
                $form->user->personalData->planned_arrival_date,
                $form->user->personalData->planned_departure_date,
                $form->can_bring,
                $form->my_best_experience,
                $form->note,
            ));
        }
        fseek($file, 0);
        $contents = '';

        while (!feof($file)) {
            $contents .= fread($file, 8192);
        }
        fclose($file);
        return response()->
            withHeader("Content-type", "text/csv") ->
            withHeader("Content-Disposition","attachment; filename=gagga_helfer.csv") ->
            withHeader("Pragma","no-cache") ->
            withHeader("Cache-Control","must-revalidate, post-check=0, pre-check=0") ->
            withContent($contents);  // stream($callback, 200, $headers);
    }

    public function check(Request $request)
    {
        $cnt = Gagga::whereUserId($this->auth->user()->id)->count();
        if ($cnt == 0) {
            $this->addNotification("pressme");
        }
    }
}
