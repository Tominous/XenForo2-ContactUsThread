<?php

namespace SV\ContactUsThread\XF\Admin\Controller;



use XF\Mvc\ParameterBag;

/**
 * Extends \XF\Admin\Controller\Banning
 */
class Banning extends XFCP_Banning
{
    protected function preDispatchController($action, ParameterBag $params)
    {
        parent::preDispatchController($action, $params);

        if (preg_match('/^emailscontact/i', $action))
        {
            $this->setSectionContext('bannedEmailsContactUs');
        }
    }

    public function actionEmailsContact()
    {
        $page = $this->filterPage();
        $perPage = 20;

        $order = $this->filter('order', 'str', 'create_date');
        $direction = $this->filter('direction', 'str', 'desc');

        $orderFields = [
            [$order, $direction]
        ];
        if ($order !== 'banned_email')
        {
            // If not already set, add this as a secondary sort because
            // majority of fields may be blank (especially legacy data)
            $orderFields[] = ['banned_email', 'asc'];
        }

        $emailBanFinder = $this->getBanningRepo()->findEmailBans()
                               ->with('User')
                               ->order($orderFields)
                               ->limitByPage($page, $perPage);
        $total = $emailBanFinder->total();

        $this->assertValidPage($page, $perPage, $total, 'banning/emails');

        $viewParams = [
            'emailBans' => $emailBanFinder->fetch(),
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'order' => $order,
            'direction' => $direction,
            'newEmail' => $this->em()->create('XF:BanEmail')
        ];
        return $this->view('XF:Banning\Email\Listing', 'sv_ban_contact_email_list', $viewParams);
    }

    public function actionEmailsContactAdd()
    {
        $this->assertPostOnly();

        $this->getBanningRepo()->banEmail(
            $this->filter('email', 'str'),
            $this->filter('reason', 'str')
        );
        return $this->redirect($this->buildLink('banning/emails'));
    }

    public function actionEmailsContactDelete()
    {
        $this->assertPostOnly();

        $deletes = $this->filter('delete', 'array-str');

        $emailBans = $this->em()->findByIds('XF:BanEmail', $deletes);
        foreach ($emailBans AS $emailBan)
        {
            $emailBan->delete();
        }

        return $this->redirect($this->buildLink('banning/emails-contact'));
    }

    public function actionEmailsContactExport()
    {
        $bannedEmails = $this->getBanningRepo()->findEmailBans();
        return $this->plugin('XF:Xml')->actionExport($bannedEmails, 'SV\ContactUsThread:BannedEmails\Export');
    }

    public function actionEmailsContactImport()
    {
        return $this->plugin('XF:Xml')->actionImport('banning/emails-contact', 'banned_emails', 'SV\ContactUsThread:BannedEmails\Import');
    }

    /**
     * @return \XF\Mvc\Entity\Repository|\SV\ContactUsThread\Repository\Banning
     */
    protected function getBanningRepo()
    {
        return $this->repository('SV\ContactUsThread:Banning');
    }
}