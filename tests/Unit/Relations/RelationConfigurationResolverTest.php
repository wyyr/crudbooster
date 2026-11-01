<?php

use crocodicstudio\crudbooster\relations\RelationConfigurationResolver;

it('finds datatable definitions inside nested child columns without rewriting them', function (): void {
    $definition = [
        'name' => 'related_record_id',
        'datatable' => ' test_related_records, label, ignored ',
        'parent_select' => ' parent_id, category_id ',
        'datatable_where' => 'active = 1',
    ];
    $resolver = new RelationConfigurationResolver([
        'invalid field',
        ['columns' => ['invalid nested field', $definition]],
    ]);

    expect($resolver->findDataTable('test_related_records', 'label', 'parent_id'))
        ->toBe($definition);
});

it('uses the first matching datatable definition', function (): void {
    $first = [
        'datatable' => 'test_related_records,label',
        'parent_select' => 'parent_id',
        'datatable_where' => 'active = 1',
    ];
    $second = [
        'datatable' => 'test_related_records,label',
        'parent_select' => 'parent_id',
        'datatable_where' => 'active = 0',
    ];
    $resolver = new RelationConfigurationResolver([$first, $second]);

    expect($resolver->findDataTable('test_related_records', 'label', 'parent_id'))
        ->toBe($first);
});

it('matches datamodal columns after trimming whitespace while preserving order', function (): void {
    $definition = [
        'datamodal_table' => 'test_related_records',
        'datamodal_columns' => ' label, category_id ',
        'datamodal_paginate' => 10,
    ];
    $resolver = new RelationConfigurationResolver([
        ['columns' => [$definition]],
    ]);

    expect($resolver->findDataModal('test_related_records', 'id, label, category_id'))
        ->toBe($definition)
        ->and($resolver->findDataModal('test_related_records', 'id,category_id,label'))
        ->toBeNull();
});

it('returns null when no relation configuration matches', function (): void {
    $resolver = new RelationConfigurationResolver([
        ['columns' => ['invalid nested field']],
    ]);

    expect($resolver->findDataTable('missing', 'label', 'parent_id'))
        ->toBeNull()
        ->and($resolver->findDataModal('missing', 'id,label'))
        ->toBeNull();
});
