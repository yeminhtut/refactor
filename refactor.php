<?php
// Please assume that classes not defined here, such as Order, Mailer, etc., are used and the implementation logic is correct
// Can add, delete, rename class, function, and please explain the reason for modification
class OrderManager {
    public $c = null;
    // generic function should be on top
    public function createRequest($method, $url, $requestBody) {
      $this->c = new Client([
          'headers' => [
              'Content-Type' => 'application/x-www-form-urlencoded',
              'Accept-Encoding' => 'gzip'
          ]
      ]);
      $response = $this->c->request($method, $url, ['body' => $requestBody]);
      return $response;
    }

    public function sendEmail($email, $subject, $content) {
      $m = new Mailer;
      $m->send($email, $subject, $content);
    }

    public function createBooking($args, $creditCard, $user) { // function name should be meaningful
        $order = new Order;
        $order->origin = $args['origin'];
        $order->destination = $args['destination'];
        $order->date = $args['date'];
        $order->currency = 'TWD';
        $order->price = $args['price'];
        $order->status = 'paying';
        $order->save();
        $response = createRequest(
          'POST',
          'https://hellopay.com/pay',
          json_encode([
            'creditCardNo' => $creditCard['no'],
            'amount' => $args['price'],
            'expireDate' => $creditCard['date'],
            'cvv' => $creditCard['cvv'],
            'key' => 'gDJSkflsdajk34fklsd4j21kgfd'
          ]));

        $order->status = $response->getStatusCode() == "200" ? 'paid' : 'payFailed';
        $order->save();
        sendEmail($user['mail'], 'Payment Fail', 'Your Order '.$order->id.' Payment Fail');
    }

    public function findOrder($id) {
        return Order::find()->where(['id' => $id])->one();
    }

    public function failedBooking() {
        $s = new Booking; // class name should be noun
        $response = createRequest('POST', 'https://helloticket.com/create', json_encode(['authKey' => '1234567890']));
        if ($response->getStatusCode() == "200") {
            $data = json_decode($response->getBody(), true);
            $s->id = $data['bookingId'];
            $s->expire = $data['expireTime'];
            $s->save();
            return $s;
        }
        return false;
    }

    public function bookFlight($booking) { // variable name should not  space, avoid negative variable name
        $response = createRequest('POST', 'https://helloticket.com/book', json_encode(['id' => $booking['bookingId']]));
        if ($response->getStatusCode() == "200") {
            return true;
        }
        return false;
    }

    public function flightBookingFailed($origin, $destination, $date) { // function name should be sneak case
        $response = createBooking(
          'POST',
          'https://helloticket.com/something-is-wrong',
          json_encode([
            'origin' => $origin,
            'destination' => $destination,
            'date' => $date
          ])
        );
        if ($response->getStatusCode() == "200") {
            $data = json_decode($response->getBody(), true);
            return [
                'origin' => $data['origin'],
                'destination' => $data['destination'],
                'datetime' => date('Y-m-d H:i:s', $data['datetime']),
                'duration' => $data['duration'],
                'airline' => $data['airline'],
                'flightNumber' => $data['flightNumber'],
            ];
        }
        return false; // default
    }
}
