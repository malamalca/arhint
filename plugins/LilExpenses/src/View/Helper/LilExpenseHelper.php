<?php
declare(strict_types=1);

namespace LilExpenses\View\Helper;

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
     * @param \LilExpenses\Model\Entity\Expense $expense Expense entity.
     * @return string
     */
    public function icon($expense)
    {
        $icon = '';
        // linked models
        switch ($expense->model) {
            case 'Invoice':
                //if (!empty($expense->invoice->no)) {
                    $icon = $this->Html->image('/lil_expenses/img/invoice.png');
                //} else {
                //  $icon = $this->Html->image('/lil_expenses/img/invoice_error.png');
                //}
                break;
            default:
                $icon = $this->Html->image('/lil_expenses/img/empty.png');
        }

        return $icon;
    }

    /**
     * Build Expense Link
     *
     * @param \LilExpenses\Model\Entity\Expense $expense Expense with linked models
     * @param bool $forceExpenseView Force expense view
     * @return string
     */
    public function link($expense, $forceExpenseView = false)
    {
        $title = $expense->title;
        if (empty($title)) {
            $title = __d('lil_expenses', 'N/A');
        }
        // this is default expense link
        $link = $this->Html->link(
            $title,
            [
                'plugin' => 'LilExpenses',
                'controller' => 'Expenses',
                'action' => 'view',
                $expense->id,
            ]
        );

        // linked models
        if (!$forceExpenseView) {
            switch ($expense->model) {
                case 'Invoice':
                    if (!Plugin::isLoaded('LilInvoices')) {
                        break;
                    }
                    $i_caption = '%1$s <span class="light">(%2$s)</span>';

                    if (!empty($expense->invoice->no)) {
                        $link = sprintf(
                            $i_caption,
                            $link = $this->Html->link(
                                !empty($expense->invoice->no) ? $expense->invoice->no : __d('lil_expenses', 'N/A'),
                                [
                                    'plugin' => 'LilInvoices',
                                    'controller' => 'Invoices',
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
     * @param \LilExpenses\Model\Entity\Expense $expense Expense with linked models
     * @return string
     */
    public function title($expense)
    {
        // this is default expense link
        $title = $expense->title;
        if (empty($title)) {
            $title = __d('lil_expenses', 'N/A');
        }

        // linked models
        switch ($expense->model) {
            /*case 'Invoice':
                $i_caption = '%1$s <span class="light">(%2$s)</span>';

                if (!empty($expense->invoice->no)) {
                    $title = sprintf(
                        $i_caption,
                        $expense->invoice->no ? $expense->invoice->no : __d('lil_expenses', 'N/A'),
                        $expense->invoice->title
                    );
                }
                break;*/
        }

        return $title;
    }

    /**
     * Build Expense Label
     *
     * @param \LilExpenses\Model\Entity\Expense $expense Expense with linked models
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
            $title = __d('lil_expenses', 'N/A');
        }

        // linked models
        switch ($expense->model) {
            /*case 'Invoice':
                $i_caption = '%1$s <span class="light">(%2$s)</span>';

                if (!empty($expense->invoice->no)) {
                    $title = sprintf(
                        $i_caption,
                        $expense->invoice->no ? $expense->invoice->no : __d('lil_expenses', 'N/A'),
                        $expense->invoice->title
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
