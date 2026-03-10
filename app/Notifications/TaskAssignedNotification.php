<?php

namespace App\Notifications;

use App\Models\Task;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TaskAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected Task $task
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $project = $this->task->project;

        return (new MailMessage)
            ->subject("New Task Assigned: {$this->task->title}")
            ->greeting("Hello {$notifiable->name},")
            ->line("You have been assigned a new task in the **{$project->title}** project.")
            ->line("**Task:** {$this->task->title}")
            ->line("**Status:** {$this->task->status}")
            ->when($this->task->due_date, function (MailMessage $mail) {
                return $mail->line("**Due Date:** {$this->task->due_date->toDateString()}");
            })
            ->when($this->task->description, function (MailMessage $mail) {
                return $mail->line("**Description:** {$this->task->description}");
            })
            ->line('Please log in to the Project Management System to view your task details.')
            ->salutation('Best regards, PMS Team');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'task_id'      => $this->task->id,
            'task_title'   => $this->task->title,
            'project_id'   => $this->task->project_id,
            'project_name' => $this->task->project?->title,
            'due_date'     => $this->task->due_date?->toDateString(),
            'status'       => $this->task->status,
            'message'      => "You have been assigned to task: {$this->task->title}",
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return $this->toDatabase($notifiable);
    }
}
