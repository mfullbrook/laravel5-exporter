<?php

namespace MwbExporter\Formatter\Laravel5;

use MwbExporter\Formatter\Laravel5\Model\Formatter;
use MwbExporter\Model\Table as ModelTable;

class BaseTable extends ModelTable
{
    protected $order;

    public function getNamespace()
    {
        if ($domain = $this->getDomain()) {
            return "App\\Domain\\Model\\$domain";
        } else {
            return $this->getConfig()->get(Formatter::CFG_NAMESPACE);
        }
    }

    protected function getDomain()
    {
        if ($domain = trim($this->parseComment('domain'))) {
            return $domain;
        }
        return '';
    }

    protected function getOrder()
    {
        if (!isset($this->order)) {
            $o = trim($this->parseComment('order'));
            $o = strlen($o) ? $o : 99;
            $this->order = str_pad($o, 2, 0, STR_PAD_LEFT);
        }
        return $this->order;
    }

    protected function getVars()
    {
        return array_merge(parent::getVars(), [
            '%domain%' => $this->getDomain(),
            '%order%' => $this->getOrder()
        ]);
    }

    public function getSortValue()
    {
        return $this->getOrder().$this->getModelName();
    }
}