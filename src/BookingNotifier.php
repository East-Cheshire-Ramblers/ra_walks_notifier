<?php

declare(strict_types=1);

namespace WalksManagerWatch;

final class BookingNotifier
{
    /**
     * @param array{
     *     title:string,
     *     organiser:string,
     *     organiser_email:string,
     *     max_bookings:int,
     *     booking1?:string,
     *     booking2?:string
     * } $event
     * @param array{preferred_name:string,email:string,created:string,state:int} $booker
     * @param array<int,array{
     *     preferred_name:string,
     *     status:string,
     *     state:int,
     *     created:string,
     *     num_places:int,
     *     partner?:string,
     *     special_request?:string,
     *     custom1?:string,
     *     custom2?:string
     * }> $bookings
     *
     * @return array<int,array{to:string,subject:string,body:string}>
     */
    public function renderMessages(array $event, array $booker, array $bookings, bool $notifyOrganiser): array
    {
        $messages = [
            [
                'to' => $booker['email'],
                'subject' => 'Your booking for ' . $event['title'],
                'body' => 'Dear ' . $booker['preferred_name'] . ',<br>' . $this->bookingDetails($event, $booker),
            ],
        ];

        if (!$notifyOrganiser) {
            return $messages;
        }

        $messages[] = [
            'to' => $event['organiser_email'],
            'subject' => ($booker['state'] === 0 ? 'New booking for ' : 'Updated booking for ') . $event['title'],
            'body' => $this->organiserBody($event, $booker, $bookings),
        ];

        return $messages;
    }

    /**
     * @param array{title:string,organiser:string,max_bookings:int,booking1?:string,booking2?:string} $event
     * @param array{preferred_name:string,created:string,state:int} $booker
     * @param array<int,array{
     *     preferred_name:string,
     *     status:string,
     *     state:int,
     *     created:string,
     *     num_places:int,
     *     partner?:string,
     *     special_request?:string,
     *     custom1?:string,
     *     custom2?:string
     * }> $bookings
     */
    private function organiserBody(array $event, array $booker, array $bookings): string
    {
        $body = 'Dear ' . $event['organiser'] . ',<br><br>';

        if ($booker['state'] === 0) {
            $body .= 'This is to notify you that there has been a new booking for your event ';
        } else {
            $body .= 'This is to notify you that a booking has been updated for your event ';
        }

        $body .= '<b>' . $event['title'] . '</b>.<br><br>';
        $body .= '<br> ' . $booker['preferred_name'] . ' made a booking at ' . date('H:i', strtotime($booker['created']));
        $body .= ' on ' . date('d M y', strtotime($booker['created'])) . '<br><br>';
        $body .= 'The list of bookings is now:<br>';
        $body .= '<table>';
        $body .= '<tr><th>Date</th><th>Name</th><th>Status</th><th>Places</th><th>Details</th></tr>';

        $provisional = 0;
        $confirmed = 0;

        foreach ($bookings as $booking) {
            if ($booking['state'] === 0) {
                $provisional += $booking['num_places'];
            } else {
                $confirmed += $booking['num_places'];
            }

            $details = ($booking['partner'] ?? '') . ', ' . ($booking['special_request'] ?? '');

            if (($event['booking1'] ?? '') !== '') {
                $details .= ',' . ($booking['custom1'] ?? '');
            }

            if (($event['booking2'] ?? '') !== '') {
                $details .= ',' . ($booking['custom2'] ?? '');
            }

            $body .= '<tr>';
            $body .= '<td>' . date('d M y H:i', strtotime($booking['created'])) . '</td>';
            $body .= '<td>' . $booking['preferred_name'] . '</td>';
            $body .= '<td>' . $booking['status'] . '</td>';
            $body .= '<td>' . $booking['num_places'] . '</td>';
            $body .= '<td>' . $details . '</td>';
            $body .= '</tr>';
        }

        $body .= '</table>';
        $body .= 'Total possible places: ' . $event['max_bookings'] . '<br>';
        $body .= 'Confirmed places: ' . $confirmed . '<br>';

        if ($confirmed + $provisional > $event['max_bookings']) {
            $body .= '<div style="color: red;">Provisional places: ' . $provisional . ' <b>(Over subscribed!)</b></div>';
        } else {
            $body .= 'Provisional places: ' . $provisional;
        }

        return $body;
    }

    /**
     * @param array{title:string} $event
     * @param array{state:int} $booker
     */
    private function bookingDetails(array $event, array $booker): string
    {
        $status = $booker['state'] === 0 ? 'provisional' : 'confirmed';

        return 'Your booking for <b>' . $event['title'] . '</b> is ' . $status . '.<br>';
    }
}
