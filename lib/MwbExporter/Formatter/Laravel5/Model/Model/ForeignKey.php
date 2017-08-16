<?php

/*
 * The MIT License
 *
 * Copyright (c) 2016 Mateus Vitali <mateus.c.vitali@gmail.com>
 * Copyright (c) 2012-2014 Toha <tohenk@yahoo.com>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace MwbExporter\Formatter\Laravel5\Model\Model;

use MwbExporter\Model\ForeignKey as BaseForeignKey;
use MwbExporter\Writer\WriterInterface;
use Doctrine\Common\Inflector\Inflector;

class ForeignKey extends BaseForeignKey
{
    public function write(WriterInterface $writer)
    {
        $method = Inflector::camelize(
            Inflector::singularize(
                $this->getReferencedTable()->getRawTableName()
            )
        );
        $writer
            ->write('/**')
            ->write(' * Relationship with ' . $this->getReferencedTable()->getModelName() . '.')
            ->write(' */')   
            ->write('public function ' . $method . '()')
            ->write('{')  
            ->indent()
                ->write('return $this->belongsTo(\'' . $this->getReferencedTable()->getNamespace() . '\\' . $this->getReferencedTable()->getModelName() . '\', \'' . $this->getLocal()->getColumnName() . '\');')   
            ->outdent()
            ->write('}')
            ->write('');
        ;

        return $this;
    }
}