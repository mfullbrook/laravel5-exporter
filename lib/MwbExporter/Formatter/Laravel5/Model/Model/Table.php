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

use MwbExporter\Model\PhysicalModel;
use MwbExporter\Formatter\Laravel5\BaseTable;
use MwbExporter\Formatter\Laravel5\Model\Formatter;
use MwbExporter\Writer\WriterInterface;
use MwbExporter\Helper\Comment;
use Doctrine\Common\Inflector\Inflector;

class Table extends BaseTable
{


    public function getParentClass()
    {
        return $this->translateVars($this->getConfig()->get(Formatter::CFG_PARENT_CLASS));
    }

    public function getParentClassBasename()
    {
        return basename(str_replace('\\', '/', $this->getParentClass()));
    }

    public function isParentClassDifferentNamespace()
    {
        return $this->getNamespace() !=
            str_replace('/', '\\', dirname(str_replace('\\', '/', $this->getParentClass())));
    }

    public function writeTable(WriterInterface $writer)
    {
        if (!$this->isExternal() && !$this->isManyToMany()) {
            // $this->getModelName() return singular form with correct camel case
            // $this->getRawTableName() return original form with no camel case
            $writer
                ->open($this->getTableFileName())
                ->write('<?php')
                ->write('')
                ->write('namespace ' . $this->getNamespace() . ';')
                ->write('')
                ->writeIf($this->isParentClassDifferentNamespace(), 'use '.$this->getParentClass().';')
                ->write('')
                ->writeCallback(function(WriterInterface $writer, Table $_this = null) {
                    if ($_this->getConfig()->get(Formatter::CFG_ADD_COMMENT)) {
                        $writer
                            ->write($_this->getFormatter()->getComment(Comment::FORMAT_PHP))
                            ->write('')
                        ;
                    }
                })
                ->write('class ' . $this->getModelName() . ' extends '. $this->getParentClassBasename())
                ->write('{')
                ->indent()
                    ->write('/**')
                    ->write(' * The database table used by the model.')
                    ->write(' * ')
                    ->write(' * @var string')
                    ->write(' */')
                    ->write('protected $table = \''. $this->getRawTableName() .'\';')
                    ->write('')                 
                    ->writeCallback(function(WriterInterface $writer, Table $_this = null) {
                        if ($_this->getConfig()->get(Formatter::CFG_GENERATE_FILLABLE)) {
                            $_this->writeFillable($writer);
                        }
                    })
                    ->writeCallback(function(WriterInterface $writer, Table $_this = null) {
                        $_this->writeRelationships($writer);
                    })
                    ->writeCallback(function(WriterInterface $writer, Table $_this = null) {
                        $_this->writeReferences($writer);
                    })
                ->outdent()
                ->write('}')
                ->write('')
                ->close()
            ;

            return self::WRITE_OK;
        }

        return self::WRITE_EXTERNAL;
    }
    
    public function writeReferences(WriterInterface $writer) 
    {
        $writer
            ->writeCallback(function(WriterInterface $writer, Table $_this = null) {
                if (count($_this->getColumns())) {
                    // Get current column from this table
                    foreach ($_this->getColumns() as $column) {
                        // Get tables from the same schema
                        foreach ($this->getParent() as $table) {  

                            // If not a pivot table
                            if(!$table->isManyToMany()) {
                                // Get foreignKeys from table
                                foreach ($table->getForeignKeys() as $foreignKey) {
                                    // If current column is referenced by foreignKey
                                    if(($_this->getRawTableName() == $foreignKey->getReferencedTable()->getRawTableName()) &&
                                        ($column->getColumnName() == $foreignKey->getForeign()->getColumnName())) {
                                        // Comment                                        
                                        $writer->write('/**');
                                        $writer->write(' * Relationship with ' . $foreignKey->getOwningTable()->getModelName() . '.');
                                        $writer->write(' */'); 
                                        // Start Method
                                        $writer->write('public function ' . Inflector::pluralize($foreignKey->getOwningTable()->getRawTableName()) . '()');            
                                        $writer->write('{');       
                                        $writer->indent();
                                        // One to Many
                                        if($foreignKey->isManyToOne()) {
                                            $writer->write('return $this->hasMany(\'' . $_this->getNamespace() . '\\' . $foreignKey->getOwningTable()->getModelName() . '\');');                      
                                        } 
                                        // One to One
                                        else {
                                            $writer->write('return $this->hasOne(\'' . $_this->getNamespace() . '\\' . $foreignKey->getOwningTable()->getModelName() . '\');');                                  
                                        }                
                                        // End Method                                                                     
                                        $writer->outdent();
                                        $writer->write('}');   
                                        $writer->write('');      
                                    }
                                }
                            } else {
                                if(count($table->getForeignKeys()) == 2) {
                                    // ForeignKey 1
                                    $foreignKey1 = $table->getForeignKeys()[0];
                                    // ForeignKey 2
                                    $foreignKey2 = $table->getForeignKeys()[1];

                                    // If current column is referenced by foreignKey
                                    if((($_this->getRawTableName() == $foreignKey1->getReferencedTable()->getRawTableName()) ||
                                        ($_this->getRawTableName() == $foreignKey2->getReferencedTable()->getRawTableName())) &&
                                        ($column->getColumnName() == $foreignKey1->getForeign()->getColumnName() ||
                                         $column->getColumnName() == $foreignKey2->getForeign()->getColumnName())) {

                                        // Comment                                        
                                        $writer->write('/**');
                                        if($_this->getRawTableName() != $foreignKey1->getReferencedTable()->getRawTableName()) {
                                            $writer->write(' * Relationship with ' . $foreignKey1->getReferencedTable()->getModelName() . '.');
                                        } else {
                                            $writer->write(' * Relationship with ' . $foreignKey2->getReferencedTable()->getModelName() . '.');
                                        }
                                        $writer->write(' */'); 

                                        // Method
                                        if($_this->getRawTableName() != $foreignKey1->getReferencedTable()->getRawTableName()) {
                                            $writer->write('public function ' . Inflector::pluralize($foreignKey1->getReferencedTable()->getRawTableName()) . '()');     
                                        } else {
                                            $writer->write('public function ' . Inflector::pluralize($foreignKey2->getReferencedTable()->getRawTableName()) . '()');     
                                        }
     
                                        $writer->write('{');       
                                        $writer->indent();   
                                        // Find out what foreignKey is this reference table and what the other table
                                        if($_this->getRawTableName() != $foreignKey1->getReferencedTable()->getRawTableName()) {
                                            $writer->write('return $this->belongsToMany(\'' . $_this->getNamespace() . '\\' . $foreignKey1->getReferencedTable()->getModelName() . '\', \'' . $foreignKey1->getOwningTable()->getRawTableName() . '\', \'' . $foreignKey2->getForeign()->getColumnName() . '\', \'' . $foreignKey1->getForeign()->getColumnName() . '\');');  
                                        } else {
                                            $writer->write('return $this->belongsToMany(\'' . $_this->getNamespace() . '\\' . $foreignKey2->getReferencedTable()->getModelName() . '\', \'' . $foreignKey2->getOwningTable()->getRawTableName() . '\', \'' . $foreignKey1->getForeign()->getColumnName() . '\', \'' . $foreignKey2->getForeign()->getColumnName() . '\');');  
                                        }                                                            
                                        $writer->outdent();
                                        $writer->write('}');   
                                        $writer->write('');
                                    }
                                }
                            }
                        }
                    }
                }
            })
        ;

        return $this;
    }

    public function writeRelationships(WriterInterface $writer) 
    {
        $writer
            ->writeCallback(function(WriterInterface $writer, Table $_this = null) {
                if (count($_this->getForeignKeys())) {
                    foreach ($_this->getForeignKeys() as $foreignKey) {
                        $foreignKey->write($writer);
                    }
                }
            })
        ;

        return $this;
    }

    public function writeFillable(WriterInterface $writer)
    {
        /*
         * FIXME: identify which columns are FK and not add to the array fillable
         */
        $writer
            ->write('/**')
            ->write(' * The attributes that are mass assignable.')
            ->write(' * ')
            ->write(' * @var array')
            ->write(' */')   
            ->writeCallback(function(WriterInterface $writer, Table $_this = null) {
                if (count($_this->getColumns())) {
                    $content = '';
                    $columns = $_this->getColumns();
                    foreach ($columns as $column) {
                        $content .= '\'' . $column->getColumnName() . '\',';
                    }
                    $writer->write('protected $fillable = [' . substr($content, 0, -1) . '];');
                } 
            })
        ;

        return $this;
    }
}