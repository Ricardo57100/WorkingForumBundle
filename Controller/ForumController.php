<?php

namespace Yosimitso\WorkingForumBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Yosimitso\WorkingForumBundle\Controller\BaseController;
use Yosimitso\WorkingForumBundle\Form\RulesType;
use Yosimitso\WorkingForumBundle\Util\CacheManager;
/**
 * Class ForumController
 *
 * @package Yosimitso\WorkingForumBundle\Controller
 */
class ForumController extends BaseController
{
    /**
     * Display homepage of forum with subforums
     *
     * @return Response
     */

    public function indexAction()
    {

        $listForum = $this->entityProxy->getForums();

        $parameters  = [ // PARAMETERS USED BY TEMPLATE
            'dateFormat' => $this->container->getParameter('yosimitso_working_forum.date_format')
            ];

        return $this->templating->renderResponse(
            '@YosimitsoWorkingForum/Forum/index.html.twig',
            [
                'list_forum' => $listForum,
                'parameters' => $parameters
            ]
        );
    }

    /**
     * Display the thread list of a subforum
     *
     * @param         $subforum_slug
     * @param Request $request
     * @param int $page
     *
     * @return Response
     */
    public function subforumAction($subforum_slug, Request $request, $page = 1)
    {
        $subforum = $this->entityProxy->getSubforum($subforum_slug);
        if (!$this->authorization->hasSubforumAccess($subforum)) {

            return $this->templating->renderResponse(
                '@YosimitsoWorkingForum/Forum/thread_list.html.twig',
                [
                    'subforum' => $subforum,
                    'forbidden' => true,
                    'forbiddenMsg' => $this->authorization->getErrorMessage()
                ]
            );
        }

        $thread_list_query = $this->entityProxy->getThreadsBySubforum($subforum);
        $date_format = $this->getParameter('yosimitso_working_forum.date_format');

        $thread_list = $this->paginator->paginate(
            $thread_list_query,
            $request->query->get('page', 1)/*page number*/,
            $this->getParameter('yosimitso_working_forum.thread_per_page') /*limit per page*/
        );

        return $this->templating->renderResponse(
            '@YosimitsoWorkingForum/Forum/thread_list.html.twig',
            [
                'subforum' => $subforum,
                'thread_list' => $thread_list,
                'date_format' => $date_format,
                'forbidden' => false,
                'post_per_page' => $this->getParameter('yosimitso_working_forum.post_per_page'),
                'page_prefix' => 'page'
            ]
        );
    }

    public function rulesAction($locale = null)
    {
        if (is_null($locale)) {
            $rulesList = $this->em->getRepository('Yosimitso\WorkingForumBundle\Entity\Rules')->findAll();

            if (!is_null($rulesList)) {
                $rules = $rulesList[0];
            } else {
                $rules = null;
            }
        } else {
            $rules = $this->em->getRepository('Yosimitso\WorkingForumBundle\Entity\Rules')->findOneByLang($lcoale);
        }

        $form = $this->createForm(RulesType::class, null);
        
        return $this->templating->renderResponse(
            '@YosimitsoWorkingForum/Forum/rules.html.twig',
            [
                'rules' => $rules,
                'form' => $form->createView()
            ]
        );
    }
}
