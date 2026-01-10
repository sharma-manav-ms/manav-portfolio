<?php
/**
 * PHP Email Form - A lightweight PHP class for form submission and validation
 * Version: 1.0
 * License: GNU General Public License v3.0
 */

class PHP_Email_Form {

  public $to = '';
  public $from_name = '';
  public $from_email = '';
  public $subject = '';
  public $smtp = array();
  public $ajax = false;
  private $messages = array();

  /**
   * Add message to email body
   */
  public function add_message($message, $label = '', $lines = 1) {
    $this->messages[] = array(
      'message' => $message,
      'label' => $label,
      'lines' => $lines
    );
  }

  /**
   * Send email
   */
  public function send() {
    if (empty($this->to)) {
      return $this->ajax ? 'Error: Recipient email not set' : 'error';
    }

    $message_body = '';
    foreach ($this->messages as $item) {
      if (!empty($item['label'])) {
        $message_body .= $item['label'] . ': ' . $item['message'] . "\n";
      } else {
        $message_body .= $item['message'] . "\n";
      }
    }

    $headers = "From: {$this->from_name} <{$this->from_email}>\r\n";
    $headers .= "Reply-To: {$this->from_email}\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Use SMTP if configured
    if (!empty($this->smtp)) {
      return $this->send_via_smtp($message_body, $headers);
    }

    // Use PHP mail function
    if (mail($this->to, $this->subject, $message_body, $headers)) {
      return $this->ajax ? 'success' : 'OK';
    } else {
      return $this->ajax ? 'error' : 'error';
    }
  }

  /**
   * Send via SMTP
   */
  private function send_via_smtp($message_body, $headers) {
    // Basic SMTP implementation - in production use PHPMailer or SwiftMailer
    try {
      $smtp_host = $this->smtp['host'] ?? 'localhost';
      $smtp_port = $this->smtp['port'] ?? 587;
      $smtp_user = $this->smtp['username'] ?? '';
      $smtp_pass = $this->smtp['password'] ?? '';

      // Open connection
      $socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 5);
      if (!$socket) {
        return $this->ajax ? 'error' : 'error';
      }

      fgets($socket);

      // Send EHLO
      fputs($socket, "EHLO localhost\r\n");
      fgets($socket);

      // Start TLS
      fputs($socket, "STARTTLS\r\n");
      fgets($socket);
      stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

      // Send EHLO again
      fputs($socket, "EHLO localhost\r\n");
      fgets($socket);

      // Authenticate
      if (!empty($smtp_user) && !empty($smtp_pass)) {
        fputs($socket, "AUTH LOGIN\r\n");
        fgets($socket);
        fputs($socket, base64_encode($smtp_user) . "\r\n");
        fgets($socket);
        fputs($socket, base64_encode($smtp_pass) . "\r\n");
        fgets($socket);
      }

      // Send mail
      fputs($socket, "MAIL FROM: <{$this->from_email}>\r\n");
      fgets($socket);
      fputs($socket, "RCPT TO: <{$this->to}>\r\n");
      fgets($socket);
      fputs($socket, "DATA\r\n");
      fgets($socket);

      fputs($socket, "Subject: {$this->subject}\r\n");
      fputs($socket, $headers);
      fputs($socket, "\r\n" . $message_body . "\r\n.\r\n");
      fgets($socket);

      // Quit
      fputs($socket, "QUIT\r\n");
      fclose($socket);

      return $this->ajax ? 'success' : 'OK';
    } catch (Exception $e) {
      return $this->ajax ? 'error' : 'error';
    }
  }
}
?>
