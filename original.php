<?php
// 請假設未於此定義的 class，如 Order、Mailer 等 class 的使用以及實作邏輯是正確的
// 可任意增刪及 rename class、function，並請說明修改原因
class OrderManager {
    public $c = null;
 
    public function do($args, $creditCard, $user) {
        $this->c = new Client([
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept-Encoding' => 'gzip'
            ]
        ]);
        $order = new Order;
        $order->origin = $args['origin'];
        $order->destination = $args['destination'];
        $order->date = $args['date'];
        $order->currency = 'TWD';
        $order->price = $args['price'];
        $order->status = 'paying';
        $order->save();
        $response = $this->c->request('POST', 'https://hellopay.com/pay', ['body' => json_encode(['creditCardNo' => $creditCard['no'], 'amount' => $args['price'], 'expireDate' => $creditCard['date'], 'cvv' => $creditCard['cvv'], 'key' => 'gDJSkflsdajk34fklsd4j21kgfd'])]);
        if ($response->getStatusCode() == "200") {
            $order->status = 'paid';
            $order->save();
            $m = new Mailer;
            $m->send($user['mail'], '付款成功', '您的訂單 '.$order->id.' 已付款成功');
        }
        else {
            $order->status = 'payFailed';
            $order->save();
            $m = new Mailer;
            $m->send($user['mail'], '付款失敗', '您的訂單 '.$order->id.' 付款失敗');
        }
    }
 
    public function findOrder($id) {
        return Order::find()->where(['id' => $id])->one();
    }
 
    public function createBookingSession() {
        $s = new BookingSession;
        $this->c = new Client([
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept-Encoding' => 'gzip'
            ]
        ]);
        $response = $this->c->request('POST', 'https://helloticket.com/create', ['body' => json_encode(['authKey' => '1234567890'])]);
        if ($response->getStatusCode() == "200") {
            $data = json_decode($response->getBody(), true);
            $s->id = $data['bookingId'];
            $s->expire = $data['expireTime'];
            $s->save();
            return $s;
        }
        else {
            return false;
        }
    }
 
    public function bookFlight($bookingSession) {
        $this->c = new Client([
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept-Encoding' => 'gzip'
            ]
        ]);
        $response = $this->c->request('POST', 'https://helloticket.com/book', ['body' => json_encode(['id' => $bookingSession['bookingId']])]);
        if ($response->getStatusCode() == "200") {
            return true;
        }
        else {
            return false;
        }
    }
 
    public function queryFlight($origin, $destination, $date) {
        $this->c = new Client([
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept-Encoding' => 'gzip'
            ]
        ]);
        $response = $this->c->request('POST', 'https://helloticket.com/query', ['body' => json_encode(['origin' => $origin, 'destination' => $destination, 'date' => $date])]);
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
        else {
            return false;
        }
    }
}
