<?php

class Controllers_Feedback extends BaseController
{
    
    function __construct()
    {
        
        $html                 = MG::get('pages')->getPageByUrl('feedback');
        $html['html_content'] = MG::inlineEditor(PREFIX . 'page', "html_content", $html['id'], $html['html_content']);
        
        $data = array(
            'dislpayForm' => true,
            'meta_title' => $html['meta_title'] ? $html['meta_title'] : $html['title'],
            'meta_keywords' => $html['meta_keywords'],
            'meta_desc' => $html['meta_desc'],
            'html_content' => $html['html_content'],
            'title' => $html['title']
        );
        
        
        if (isset($_POST['send'])) {
            
            $feedBack = new Models_Feedback;
            
            $error         = $feedBack->isValidData($_POST);
            $data['error'] = $error;
            
            if (!$error) {
                $_POST['message'] = MG::nl2br($_POST['message']);
                $sitename         = MG::getSetting('sitename');
                $body             = MG::layoutManager('email_feedback', array(
                    'msg' => $_POST['message'],
                    'email' => $feedBack->getEmail(),
                    'name' => $feedBack->getFio()
                ));
                
                $mails = explode(',', MG::getSetting('adminEmail'));
                foreach ($mails as $mail) {
                    if (preg_match('/^[-._a-zA-Z0-9]+@(?:[a-zA-Z0-9][-a-zA-Z0-9]+\.)+[a-zA-Z]{2,6}$/', $mail)) {
                        Mailer::addHeaders(array(
                            "Reply-to" => $feedBack->getEmail()
                        ));
                        Mailer::sendMimeMail(array(
                            'nameFrom' => $feedBack->getFio(),
                            'emailFrom' => $feedBack->getEmail(),
                            'nameTo' => $sitename,
                            'emailTo' => $mail,
                            'subject' => '    ',
                            'body' => $body,
                            'html' => true
                        ));
                    }
                }
                
                MG::redirect('/feedback?thanks=1');
            }
        }
        
        if (isset($_REQUEST['thanks'])) {
            $data = array(
                'message' => '  !',
                'dislpayForm' => false,
                'meta_title' => ' ',
                'meta_keywords' => $model->currentCategory['meta_keywords'] ? $model->currentCategory['meta_keywords'] : " ,  ,   ",
                'meta_desc' => $model->currentCategory['meta_desc'] ? $model->currentCategory['meta_desc'] : "       ."
            );
        }
        
        $this->data = $data;
    }
    
}