<?php

namespace App\Notifications;

use App\Models\GuruCourseOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class GuruCourseOfferNotification extends Notification
{
    use Queueable;

    public function __construct(private readonly GuruCourseOffer $offer)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $targetSemester = (int) $this->offer->target_semester;
        $sourceSemester = max(1, $targetSemester - 1);

        return [
            'guru_course_offer_id' => $this->offer->id,
            'notification_title' => 'Tawaran Kursus Guru',
            'notification_meta' => 'Semester ' . $sourceSemester . ' -> Semester ' . $targetSemester,
            'notification_message' => 'Adakah anda mahu sambung ke Semester ' . $targetSemester . '? Sila jawab sebelum ' . optional($this->offer->registration_deadline)?->format('d/m/Y') . '.',
            'url' => route('kursus-guru.index'),
        ];
    }
}
