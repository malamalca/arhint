<?php
declare(strict_types=1);

namespace Expenses\View\Helper;

use Cake\Core\Plugin;
use Cake\View\Helper;

/**
 * LilExpenseHelper class
 *
 * @property \Cake\View\Helper\HtmlHelper $Html
 * @property \Cake\View\Helper\NumberHelper $Number
 */
class LilExpenseHelper extends Helper
{
    public $helpers = ['Html', 'Number'];

    /**
     * Build Expense icon
     *
     * @param \Expenses\Model\Entity\Expense $expense Expense entity.
     * @return string
     */
    public function icon($expense)
    {
        $icon = '';
        // linked models
        switch ($expense->model) {
            case 'Document':
                //if (!empty($expense->document->no)) {
                    $icon = $this->Html->image('/expenses/img/document.png');
                //} else {
                //  $icon = $this->Html->image('/expenses/img/document_error.png');
                //}
                break;
            default:
                $icon = $this->Html->image('/expenses/img/empty.png');
        }

        return $icon;
    }

    /**
     * Build Expense Link
     *
     * @param \Expenses\Model\Entity\Expense $expense Expense with linked models
     * @param bool $forceExpenseView Force expense view
     * @return string
     */
    public function link($expense, $forceExpenseView = false)
    {
        $title = $expense->title;
        if (empty($title)) {
            $title = __d('expenses', 'N/A');
        }
        // this is default expense link
        $link = $this->Html->link(
            $title,
            [
                'plugin' => 'Expenses',
                'controller' => 'Expenses',
                'action' => 'view',
                $expense->id,
            ]
        );

        // linked models
        if (!$forceExpenseView) {
            switch ($expense->model) {
                case 'Document':
                    if (!Plugin::isLoaded('Documents')) {
                        break;
                    }
                    $i_caption = '%1$s <span class="light">(%2$s)</span>';

                    if (!empty($expense->document->no)) {
                        $link = sprintf(
                            $i_caption,
                            $link = $this->Html->link(
                                !empty($expense->document->no) ? $expense->document->no : __d('expenses', 'N/A'),
                                [
                                    'plugin' => 'Documents',
                                    'controller' => 'Documents',
                                    'action' => 'view',
                                    $expense->foreign_id,
                                ]
                            ),
                            $expense->title
                        );
                    }
                    break;
            }
        }

        return $link;
    }

    /**
     * Build Expense Title
     *
     * @param \Expenses\Model\Entity\Expense $expense Expense with linked models
     * @return string
     */
    public function title($expense)
    {
        // this is default expense link
        $title = $expense->title;
        if (empty($title)) {
            $title = __d('expenses', 'N/A');
        }

        // linked models
        switch ($expense->model) {
            /*case 'Document':
                $i_caption = '%1$s <span class="light">(%2$s)</span>';

                if (!empty($expense->document->no)) {
                    $title = sprintf(
                        $i_caption,
                        $expense->document->no ? $expense->document->no : __d('expenses', 'N/A'),
                        $expense->document->title
                    );
                }
                break;*/
        }

        return $title;
    }

    /**
     * Build Expense Label
     *
     * @param \Expenses\Model\Entity\Expense $expense Expense with linked models
     * @return string
     */
    public function label($expense)
    {
        // this is default expense link
        $template =
            '<span class="ac-expense-icon">%1$s</span>' .
            '<span class="ac-expense-title">%2$s</span>' .
            '<span class="ac-expense-date">%3$s</span>' .
            '<span class="ac-expense-total">%4$s</span>';

        $title = $expense->title;
        if (empty($title)) {
            $title = __d('expenses', 'N/A');
        }

        // linked models
        switch ($expense->model) {
            /*case 'Document':
                $i_caption = '%1$s <span class="light">(%2$s)</span>';

                if (!empty($expense->document->no)) {
                    $title = sprintf(
                        $i_caption,
                        $expense->document->no ? $expense->document->no : __d('expenses', 'N/A'),
                        $expense->document->title
                    );
                }
                break;*/
        }

        $title = sprintf(
            $template,
            $this->icon($expense),
            $title,
            (string)$expense->dat_happened,
            $this->Number->precision((float)$expense->total, 2)
        );

        return $title;
    }
}
