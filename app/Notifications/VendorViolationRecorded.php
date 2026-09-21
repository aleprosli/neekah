<?php

namespace App\Notifications;

use App\Enums\ViolationAction;
use App\Models\VendorViolation;
use App\Support\NeekahMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorViolationRecorded extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public VendorViolation $violation) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * @return array<string, string>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'icon' => '⚠️',
            'title_key' => 'notifications.violation.title',
            'title_params' => ['action' => $this->violation->action->label()],
            'body_key' => 'notifications.violation.body',
            'body_params' => ['type' => $this->violation->type->label(), 'number' => $this->violation->offence_number],
            'url' => route('vendor.dashboard'),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $action = $this->violation->action;

        $message = NeekahMail::to($notifiable)
            ->subject(__('notifications.violation.subject', ['action' => $action->label()]))
            ->line(__('notifications.violation.intro', ['vendor' => $this->violation->vendor->name]))
            ->line(__('notifications.violation.type', ['type' => $this->violation->type->label()]))
            ->line(__('notifications.violation.number', ['number' => $this->violation->offence_number, 'action' => $action->label()]));

        if ($this->violation->admin_note) {
            $message->line(__('notifications.violation.admin_note', ['note' => $this->violation->admin_note]));
        }

        $message->line(match ($action) {
            ViolationAction::Warning => __('notifications.violation.warning'),
            ViolationAction::PointDeduction => __('notifications.violation.point_deduction'),
            ViolationAction::Suspension => __('notifications.violation.suspension'),
            ViolationAction::Removal => __('notifications.violation.removal'),
        });

        return $message
            ->action(__('notifications.actions.open_dashboard'), route('vendor.dashboard'));
    }
}
