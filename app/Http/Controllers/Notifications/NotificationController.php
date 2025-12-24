<?php

namespace App\Http\Controllers\Notifications;

use App\Http\Controllers\Controller;
use App\Models\Notifications\Notification;
use App\Models\Users\User;

class NotificationController extends Controller
{
    /**
     * Example on how to trigger notification
     *
     * Notification::send(auth()->user(), new NotifyUser([
     * 'channel'   => define either ['mail'] or ['database'] or ['mail','database'],
     * 'subject'   => subject for your email if notify by email,
     * 'message'   => message for the notification,
     * 'redirect'  => redirect url for the action button for mail channel,
     * 'form_id'   => provide form id if related to form,
     * 'action_title' => provide the title for action button
     * ]));
     */

    public function __construct()
    {
        $this->middleware('permission:browse-notifications')->only('index');
        $this->middleware('permission:read-notifications')->only('show');
        $this->middleware('permission:edit-notifications')->only('setAsHasRead', 'markAsRead');
        $this->middleware('permission:delete-notifications')->only('destroy', 'deleteAll');
    }

    public function index()
    {
        return view('notifications.index');
    }

    public function show($id)
    {
        $model = Notification::findOrFail($id);

        if ($model->notifiable_id != auth()->id()) {
            return redirect()->route('notifications.index');
        }

        return view('notifications.show', [
            'model' => $model,
        ]);
    }

    public function destroy($id)
    {
        $model = Notification::query()->find($id);

        if ($model) {
            if ($model->notifiable_id != auth()->id()) {
                flash()->error(__('messages.something_went_wrong'));

                return response()->json(['success' => true, 'redirect' => route('notifications.index')]);
            }

            $model->delete();

            flash()->success(__('messages.notification_successfully_deleted'));

            activity(__('messages.notifications'))
                ->causedBy(auth()->user())
                ->log(__('messages.notification_has_been_deleted', ['id' => $model->id]));
        } else {
            flash()->error(__('messages.something_went_wrong'));
        }

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('notifications.index')]);
    }

    public function setAsHasRead($id)
    {
        $model = Notification::query()->find($id);

        if ($model) {
            if ($model->notifiable_id != auth()->id()) {
                flash()->error(__('messages.something_went_wrong'));

                return response()->json(['success' => true, 'redirect' => route('notifications.index')]);
            }

            if ($model->read_at) {
                flash()->info(__('messages.notification_already_read'));
            } else {
                $model->read_at = now();
                $model->update();

                flash()->success(__('messages.notification_successfully_mark_as_has_read'));

                activity(__('messages.notifications'))
                    ->causedBy(auth()->user())
                    ->log(__('messages.notification_mark_as_has_read', ['id' => $model->id]));
            }
        } else {
            flash()->error(__('messages.something_went_wrong'));
        }

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('notifications.index')]);
    }

    public function markAsRead()
    {
        $user = User::findOrFail(auth()->id());

        $user->unreadNotifications->markAsRead();

        flash()->success(__('messages.successfully_mark_all_notification_as_has_read'));

        return back();
    }

    public function deleteAll()
    {
        $user = User::findOrFail(auth()->id());

        $user->notifications()->delete();

        flash()->success(__('messages.all_notification_successfully_deleted'));

        // Redirect
        return response()->json(['success' => true, 'redirect' => route('notifications.index')]);
    }
}
