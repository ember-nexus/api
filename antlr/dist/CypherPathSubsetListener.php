<?php

declare(strict_types=1);

/*
 * Generated from CypherPathSubset.g4 by ANTLR 4.13.2
 */

use Antlr\Antlr4\Runtime\Tree\ParseTreeListener;

/**
 * This interface defines a complete listener for a parse tree produced by
 * {@see CypherPathSubset}.
 */
interface CypherPathSubsetListener extends ParseTreeListener
{
    /**
     * Enter a parse tree produced by {@see CypherPathSubset::statements()}.
     *
     * @param $context The parse tree
     */
    public function enterStatements(Context\StatementsContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::statements()}.
     *
     * @param $context The parse tree
     */
    public function exitStatements(Context\StatementsContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::statement()}.
     *
     * @param $context The parse tree
     */
    public function enterStatement(Context\StatementContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::statement()}.
     *
     * @param $context The parse tree
     */
    public function exitStatement(Context\StatementContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::regularQuery()}.
     *
     * @param $context The parse tree
     */
    public function enterRegularQuery(Context\RegularQueryContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::regularQuery()}.
     *
     * @param $context The parse tree
     */
    public function exitRegularQuery(Context\RegularQueryContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::singleQuery()}.
     *
     * @param $context The parse tree
     */
    public function enterSingleQuery(Context\SingleQueryContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::singleQuery()}.
     *
     * @param $context The parse tree
     */
    public function exitSingleQuery(Context\SingleQueryContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::returnClause()}.
     *
     * @param $context The parse tree
     */
    public function enterReturnClause(Context\ReturnClauseContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::returnClause()}.
     *
     * @param $context The parse tree
     */
    public function exitReturnClause(Context\ReturnClauseContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::returnBody()}.
     *
     * @param $context The parse tree
     */
    public function enterReturnBody(Context\ReturnBodyContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::returnBody()}.
     *
     * @param $context The parse tree
     */
    public function exitReturnBody(Context\ReturnBodyContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::returnItem()}.
     *
     * @param $context The parse tree
     */
    public function enterReturnItem(Context\ReturnItemContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::returnItem()}.
     *
     * @param $context The parse tree
     */
    public function exitReturnItem(Context\ReturnItemContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::returnItems()}.
     *
     * @param $context The parse tree
     */
    public function enterReturnItems(Context\ReturnItemsContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::returnItems()}.
     *
     * @param $context The parse tree
     */
    public function exitReturnItems(Context\ReturnItemsContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::orderItem()}.
     *
     * @param $context The parse tree
     */
    public function enterOrderItem(Context\OrderItemContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::orderItem()}.
     *
     * @param $context The parse tree
     */
    public function exitOrderItem(Context\OrderItemContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::ascToken()}.
     *
     * @param $context The parse tree
     */
    public function enterAscToken(Context\AscTokenContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::ascToken()}.
     *
     * @param $context The parse tree
     */
    public function exitAscToken(Context\AscTokenContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::descToken()}.
     *
     * @param $context The parse tree
     */
    public function enterDescToken(Context\DescTokenContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::descToken()}.
     *
     * @param $context The parse tree
     */
    public function exitDescToken(Context\DescTokenContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::orderBy()}.
     *
     * @param $context The parse tree
     */
    public function enterOrderBy(Context\OrderByContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::orderBy()}.
     *
     * @param $context The parse tree
     */
    public function exitOrderBy(Context\OrderByContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::skip()}.
     *
     * @param $context The parse tree
     */
    public function enterSkip(Context\SkipContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::skip()}.
     *
     * @param $context The parse tree
     */
    public function exitSkip(Context\SkipContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::limit()}.
     *
     * @param $context The parse tree
     */
    public function enterLimit(Context\LimitContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::limit()}.
     *
     * @param $context The parse tree
     */
    public function exitLimit(Context\LimitContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::whereClause()}.
     *
     * @param $context The parse tree
     */
    public function enterWhereClause(Context\WhereClauseContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::whereClause()}.
     *
     * @param $context The parse tree
     */
    public function exitWhereClause(Context\WhereClauseContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::matchClause()}.
     *
     * @param $context The parse tree
     */
    public function enterMatchClause(Context\MatchClauseContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::matchClause()}.
     *
     * @param $context The parse tree
     */
    public function exitMatchClause(Context\MatchClauseContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::unwindClause()}.
     *
     * @param $context The parse tree
     */
    public function enterUnwindClause(Context\UnwindClauseContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::unwindClause()}.
     *
     * @param $context The parse tree
     */
    public function exitUnwindClause(Context\UnwindClauseContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::patternList()}.
     *
     * @param $context The parse tree
     */
    public function enterPatternList(Context\PatternListContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::patternList()}.
     *
     * @param $context The parse tree
     */
    public function exitPatternList(Context\PatternListContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::pattern()}.
     *
     * @param $context The parse tree
     */
    public function enterPattern(Context\PatternContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::pattern()}.
     *
     * @param $context The parse tree
     */
    public function exitPattern(Context\PatternContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::quantifier()}.
     *
     * @param $context The parse tree
     */
    public function enterQuantifier(Context\QuantifierContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::quantifier()}.
     *
     * @param $context The parse tree
     */
    public function exitQuantifier(Context\QuantifierContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::anonymousPattern()}.
     *
     * @param $context The parse tree
     */
    public function enterAnonymousPattern(Context\AnonymousPatternContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::anonymousPattern()}.
     *
     * @param $context The parse tree
     */
    public function exitAnonymousPattern(Context\AnonymousPatternContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::patternElement()}.
     *
     * @param $context The parse tree
     */
    public function enterPatternElement(Context\PatternElementContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::patternElement()}.
     *
     * @param $context The parse tree
     */
    public function exitPatternElement(Context\PatternElementContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::nodePattern()}.
     *
     * @param $context The parse tree
     */
    public function enterNodePattern(Context\NodePatternContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::nodePattern()}.
     *
     * @param $context The parse tree
     */
    public function exitNodePattern(Context\NodePatternContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::parenthesizedPath()}.
     *
     * @param $context The parse tree
     */
    public function enterParenthesizedPath(Context\ParenthesizedPathContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::parenthesizedPath()}.
     *
     * @param $context The parse tree
     */
    public function exitParenthesizedPath(Context\ParenthesizedPathContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::properties()}.
     *
     * @param $context The parse tree
     */
    public function enterProperties(Context\PropertiesContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::properties()}.
     *
     * @param $context The parse tree
     */
    public function exitProperties(Context\PropertiesContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::relationshipPattern()}.
     *
     * @param $context The parse tree
     */
    public function enterRelationshipPattern(Context\RelationshipPatternContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::relationshipPattern()}.
     *
     * @param $context The parse tree
     */
    public function exitRelationshipPattern(Context\RelationshipPatternContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::leftArrow()}.
     *
     * @param $context The parse tree
     */
    public function enterLeftArrow(Context\LeftArrowContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::leftArrow()}.
     *
     * @param $context The parse tree
     */
    public function exitLeftArrow(Context\LeftArrowContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::arrowLine()}.
     *
     * @param $context The parse tree
     */
    public function enterArrowLine(Context\ArrowLineContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::arrowLine()}.
     *
     * @param $context The parse tree
     */
    public function exitArrowLine(Context\ArrowLineContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::rightArrow()}.
     *
     * @param $context The parse tree
     */
    public function enterRightArrow(Context\RightArrowContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::rightArrow()}.
     *
     * @param $context The parse tree
     */
    public function exitRightArrow(Context\RightArrowContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::pathLength()}.
     *
     * @param $context The parse tree
     */
    public function enterPathLength(Context\PathLengthContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::pathLength()}.
     *
     * @param $context The parse tree
     */
    public function exitPathLength(Context\PathLengthContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::labelExpression()}.
     *
     * @param $context The parse tree
     */
    public function enterLabelExpression(Context\LabelExpressionContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::labelExpression()}.
     *
     * @param $context The parse tree
     */
    public function exitLabelExpression(Context\LabelExpressionContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::labelExpression4()}.
     *
     * @param $context The parse tree
     */
    public function enterLabelExpression4(Context\LabelExpression4Context $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::labelExpression4()}.
     *
     * @param $context The parse tree
     */
    public function exitLabelExpression4(Context\LabelExpression4Context $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::labelExpression4Is()}.
     *
     * @param $context The parse tree
     */
    public function enterLabelExpression4Is(Context\LabelExpression4IsContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::labelExpression4Is()}.
     *
     * @param $context The parse tree
     */
    public function exitLabelExpression4Is(Context\LabelExpression4IsContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::labelExpression3()}.
     *
     * @param $context The parse tree
     */
    public function enterLabelExpression3(Context\LabelExpression3Context $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::labelExpression3()}.
     *
     * @param $context The parse tree
     */
    public function exitLabelExpression3(Context\LabelExpression3Context $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::labelExpression3Is()}.
     *
     * @param $context The parse tree
     */
    public function enterLabelExpression3Is(Context\LabelExpression3IsContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::labelExpression3Is()}.
     *
     * @param $context The parse tree
     */
    public function exitLabelExpression3Is(Context\LabelExpression3IsContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::labelExpression2()}.
     *
     * @param $context The parse tree
     */
    public function enterLabelExpression2(Context\LabelExpression2Context $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::labelExpression2()}.
     *
     * @param $context The parse tree
     */
    public function exitLabelExpression2(Context\LabelExpression2Context $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::labelExpression2Is()}.
     *
     * @param $context The parse tree
     */
    public function enterLabelExpression2Is(Context\LabelExpression2IsContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::labelExpression2Is()}.
     *
     * @param $context The parse tree
     */
    public function exitLabelExpression2Is(Context\LabelExpression2IsContext $context): void;

    /**
     * Enter a parse tree produced by the `ParenthesizedLabelExpression`
     * labeled alternative in {@see CypherPathSubset::labelExpression1()}.
     *
     * @param $context The parse tree
     */
    public function enterParenthesizedLabelExpression(Context\ParenthesizedLabelExpressionContext $context): void;

    /**
     * Exit a parse tree produced by the `ParenthesizedLabelExpression` labeled alternative
     * in {@see CypherPathSubset::labelExpression1()}.
     *
     * @param $context The parse tree
     */
    public function exitParenthesizedLabelExpression(Context\ParenthesizedLabelExpressionContext $context): void;

    /**
     * Enter a parse tree produced by the `AnyLabel`
     * labeled alternative in {@see CypherPathSubset::labelExpression1()}.
     *
     * @param $context The parse tree
     */
    public function enterAnyLabel(Context\AnyLabelContext $context): void;

    /**
     * Exit a parse tree produced by the `AnyLabel` labeled alternative
     * in {@see CypherPathSubset::labelExpression1()}.
     *
     * @param $context The parse tree
     */
    public function exitAnyLabel(Context\AnyLabelContext $context): void;

    /**
     * Enter a parse tree produced by the `LabelName`
     * labeled alternative in {@see CypherPathSubset::labelExpression1()}.
     *
     * @param $context The parse tree
     */
    public function enterLabelName(Context\LabelNameContext $context): void;

    /**
     * Exit a parse tree produced by the `LabelName` labeled alternative
     * in {@see CypherPathSubset::labelExpression1()}.
     *
     * @param $context The parse tree
     */
    public function exitLabelName(Context\LabelNameContext $context): void;

    /**
     * Enter a parse tree produced by the `ParenthesizedLabelExpressionIs`
     * labeled alternative in {@see CypherPathSubset::labelExpression1Is()}.
     *
     * @param $context The parse tree
     */
    public function enterParenthesizedLabelExpressionIs(Context\ParenthesizedLabelExpressionIsContext $context): void;

    /**
     * Exit a parse tree produced by the `ParenthesizedLabelExpressionIs` labeled alternative
     * in {@see CypherPathSubset::labelExpression1Is()}.
     *
     * @param $context The parse tree
     */
    public function exitParenthesizedLabelExpressionIs(Context\ParenthesizedLabelExpressionIsContext $context): void;

    /**
     * Enter a parse tree produced by the `AnyLabelIs`
     * labeled alternative in {@see CypherPathSubset::labelExpression1Is()}.
     *
     * @param $context The parse tree
     */
    public function enterAnyLabelIs(Context\AnyLabelIsContext $context): void;

    /**
     * Exit a parse tree produced by the `AnyLabelIs` labeled alternative
     * in {@see CypherPathSubset::labelExpression1Is()}.
     *
     * @param $context The parse tree
     */
    public function exitAnyLabelIs(Context\AnyLabelIsContext $context): void;

    /**
     * Enter a parse tree produced by the `LabelNameIs`
     * labeled alternative in {@see CypherPathSubset::labelExpression1Is()}.
     *
     * @param $context The parse tree
     */
    public function enterLabelNameIs(Context\LabelNameIsContext $context): void;

    /**
     * Exit a parse tree produced by the `LabelNameIs` labeled alternative
     * in {@see CypherPathSubset::labelExpression1Is()}.
     *
     * @param $context The parse tree
     */
    public function exitLabelNameIs(Context\LabelNameIsContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::expression()}.
     *
     * @param $context The parse tree
     */
    public function enterExpression(Context\ExpressionContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::expression()}.
     *
     * @param $context The parse tree
     */
    public function exitExpression(Context\ExpressionContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::expression11()}.
     *
     * @param $context The parse tree
     */
    public function enterExpression11(Context\Expression11Context $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::expression11()}.
     *
     * @param $context The parse tree
     */
    public function exitExpression11(Context\Expression11Context $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::expression10()}.
     *
     * @param $context The parse tree
     */
    public function enterExpression10(Context\Expression10Context $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::expression10()}.
     *
     * @param $context The parse tree
     */
    public function exitExpression10(Context\Expression10Context $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::expression9()}.
     *
     * @param $context The parse tree
     */
    public function enterExpression9(Context\Expression9Context $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::expression9()}.
     *
     * @param $context The parse tree
     */
    public function exitExpression9(Context\Expression9Context $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::expression8()}.
     *
     * @param $context The parse tree
     */
    public function enterExpression8(Context\Expression8Context $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::expression8()}.
     *
     * @param $context The parse tree
     */
    public function exitExpression8(Context\Expression8Context $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::expression7()}.
     *
     * @param $context The parse tree
     */
    public function enterExpression7(Context\Expression7Context $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::expression7()}.
     *
     * @param $context The parse tree
     */
    public function exitExpression7(Context\Expression7Context $context): void;

    /**
     * Enter a parse tree produced by the `StringAndListComparison`
     * labeled alternative in {@see CypherPathSubset::comparisonExpression6()}.
     *
     * @param $context The parse tree
     */
    public function enterStringAndListComparison(Context\StringAndListComparisonContext $context): void;

    /**
     * Exit a parse tree produced by the `StringAndListComparison` labeled alternative
     * in {@see CypherPathSubset::comparisonExpression6()}.
     *
     * @param $context The parse tree
     */
    public function exitStringAndListComparison(Context\StringAndListComparisonContext $context): void;

    /**
     * Enter a parse tree produced by the `NullComparison`
     * labeled alternative in {@see CypherPathSubset::comparisonExpression6()}.
     *
     * @param $context The parse tree
     */
    public function enterNullComparison(Context\NullComparisonContext $context): void;

    /**
     * Exit a parse tree produced by the `NullComparison` labeled alternative
     * in {@see CypherPathSubset::comparisonExpression6()}.
     *
     * @param $context The parse tree
     */
    public function exitNullComparison(Context\NullComparisonContext $context): void;

    /**
     * Enter a parse tree produced by the `TypeComparison`
     * labeled alternative in {@see CypherPathSubset::comparisonExpression6()}.
     *
     * @param $context The parse tree
     */
    public function enterTypeComparison(Context\TypeComparisonContext $context): void;

    /**
     * Exit a parse tree produced by the `TypeComparison` labeled alternative
     * in {@see CypherPathSubset::comparisonExpression6()}.
     *
     * @param $context The parse tree
     */
    public function exitTypeComparison(Context\TypeComparisonContext $context): void;

    /**
     * Enter a parse tree produced by the `NormalFormComparison`
     * labeled alternative in {@see CypherPathSubset::comparisonExpression6()}.
     *
     * @param $context The parse tree
     */
    public function enterNormalFormComparison(Context\NormalFormComparisonContext $context): void;

    /**
     * Exit a parse tree produced by the `NormalFormComparison` labeled alternative
     * in {@see CypherPathSubset::comparisonExpression6()}.
     *
     * @param $context The parse tree
     */
    public function exitNormalFormComparison(Context\NormalFormComparisonContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::normalForm()}.
     *
     * @param $context The parse tree
     */
    public function enterNormalForm(Context\NormalFormContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::normalForm()}.
     *
     * @param $context The parse tree
     */
    public function exitNormalForm(Context\NormalFormContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::expression6()}.
     *
     * @param $context The parse tree
     */
    public function enterExpression6(Context\Expression6Context $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::expression6()}.
     *
     * @param $context The parse tree
     */
    public function exitExpression6(Context\Expression6Context $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::expression5()}.
     *
     * @param $context The parse tree
     */
    public function enterExpression5(Context\Expression5Context $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::expression5()}.
     *
     * @param $context The parse tree
     */
    public function exitExpression5(Context\Expression5Context $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::expression4()}.
     *
     * @param $context The parse tree
     */
    public function enterExpression4(Context\Expression4Context $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::expression4()}.
     *
     * @param $context The parse tree
     */
    public function exitExpression4(Context\Expression4Context $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::expression3()}.
     *
     * @param $context The parse tree
     */
    public function enterExpression3(Context\Expression3Context $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::expression3()}.
     *
     * @param $context The parse tree
     */
    public function exitExpression3(Context\Expression3Context $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::expression2()}.
     *
     * @param $context The parse tree
     */
    public function enterExpression2(Context\Expression2Context $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::expression2()}.
     *
     * @param $context The parse tree
     */
    public function exitExpression2(Context\Expression2Context $context): void;

    /**
     * Enter a parse tree produced by the `PropertyPostfix`
     * labeled alternative in {@see CypherPathSubset::postFix()}.
     *
     * @param $context The parse tree
     */
    public function enterPropertyPostfix(Context\PropertyPostfixContext $context): void;

    /**
     * Exit a parse tree produced by the `PropertyPostfix` labeled alternative
     * in {@see CypherPathSubset::postFix()}.
     *
     * @param $context The parse tree
     */
    public function exitPropertyPostfix(Context\PropertyPostfixContext $context): void;

    /**
     * Enter a parse tree produced by the `LabelPostfix`
     * labeled alternative in {@see CypherPathSubset::postFix()}.
     *
     * @param $context The parse tree
     */
    public function enterLabelPostfix(Context\LabelPostfixContext $context): void;

    /**
     * Exit a parse tree produced by the `LabelPostfix` labeled alternative
     * in {@see CypherPathSubset::postFix()}.
     *
     * @param $context The parse tree
     */
    public function exitLabelPostfix(Context\LabelPostfixContext $context): void;

    /**
     * Enter a parse tree produced by the `IndexPostfix`
     * labeled alternative in {@see CypherPathSubset::postFix()}.
     *
     * @param $context The parse tree
     */
    public function enterIndexPostfix(Context\IndexPostfixContext $context): void;

    /**
     * Exit a parse tree produced by the `IndexPostfix` labeled alternative
     * in {@see CypherPathSubset::postFix()}.
     *
     * @param $context The parse tree
     */
    public function exitIndexPostfix(Context\IndexPostfixContext $context): void;

    /**
     * Enter a parse tree produced by the `RangePostfix`
     * labeled alternative in {@see CypherPathSubset::postFix()}.
     *
     * @param $context The parse tree
     */
    public function enterRangePostfix(Context\RangePostfixContext $context): void;

    /**
     * Exit a parse tree produced by the `RangePostfix` labeled alternative
     * in {@see CypherPathSubset::postFix()}.
     *
     * @param $context The parse tree
     */
    public function exitRangePostfix(Context\RangePostfixContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::property()}.
     *
     * @param $context The parse tree
     */
    public function enterProperty(Context\PropertyContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::property()}.
     *
     * @param $context The parse tree
     */
    public function exitProperty(Context\PropertyContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::expression1()}.
     *
     * @param $context The parse tree
     */
    public function enterExpression1(Context\Expression1Context $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::expression1()}.
     *
     * @param $context The parse tree
     */
    public function exitExpression1(Context\Expression1Context $context): void;

    /**
     * Enter a parse tree produced by the `NummericLiteral`
     * labeled alternative in {@see CypherPathSubset::literal()}.
     *
     * @param $context The parse tree
     */
    public function enterNummericLiteral(Context\NummericLiteralContext $context): void;

    /**
     * Exit a parse tree produced by the `NummericLiteral` labeled alternative
     * in {@see CypherPathSubset::literal()}.
     *
     * @param $context The parse tree
     */
    public function exitNummericLiteral(Context\NummericLiteralContext $context): void;

    /**
     * Enter a parse tree produced by the `StringsLiteral`
     * labeled alternative in {@see CypherPathSubset::literal()}.
     *
     * @param $context The parse tree
     */
    public function enterStringsLiteral(Context\StringsLiteralContext $context): void;

    /**
     * Exit a parse tree produced by the `StringsLiteral` labeled alternative
     * in {@see CypherPathSubset::literal()}.
     *
     * @param $context The parse tree
     */
    public function exitStringsLiteral(Context\StringsLiteralContext $context): void;

    /**
     * Enter a parse tree produced by the `OtherLiteral`
     * labeled alternative in {@see CypherPathSubset::literal()}.
     *
     * @param $context The parse tree
     */
    public function enterOtherLiteral(Context\OtherLiteralContext $context): void;

    /**
     * Exit a parse tree produced by the `OtherLiteral` labeled alternative
     * in {@see CypherPathSubset::literal()}.
     *
     * @param $context The parse tree
     */
    public function exitOtherLiteral(Context\OtherLiteralContext $context): void;

    /**
     * Enter a parse tree produced by the `BooleanLiteral`
     * labeled alternative in {@see CypherPathSubset::literal()}.
     *
     * @param $context The parse tree
     */
    public function enterBooleanLiteral(Context\BooleanLiteralContext $context): void;

    /**
     * Exit a parse tree produced by the `BooleanLiteral` labeled alternative
     * in {@see CypherPathSubset::literal()}.
     *
     * @param $context The parse tree
     */
    public function exitBooleanLiteral(Context\BooleanLiteralContext $context): void;

    /**
     * Enter a parse tree produced by the `KeywordLiteral`
     * labeled alternative in {@see CypherPathSubset::literal()}.
     *
     * @param $context The parse tree
     */
    public function enterKeywordLiteral(Context\KeywordLiteralContext $context): void;

    /**
     * Exit a parse tree produced by the `KeywordLiteral` labeled alternative
     * in {@see CypherPathSubset::literal()}.
     *
     * @param $context The parse tree
     */
    public function exitKeywordLiteral(Context\KeywordLiteralContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::parenthesizedExpression()}.
     *
     * @param $context The parse tree
     */
    public function enterParenthesizedExpression(Context\ParenthesizedExpressionContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::parenthesizedExpression()}.
     *
     * @param $context The parse tree
     */
    public function exitParenthesizedExpression(Context\ParenthesizedExpressionContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::numberLiteral()}.
     *
     * @param $context The parse tree
     */
    public function enterNumberLiteral(Context\NumberLiteralContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::numberLiteral()}.
     *
     * @param $context The parse tree
     */
    public function exitNumberLiteral(Context\NumberLiteralContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::propertyKeyName()}.
     *
     * @param $context The parse tree
     */
    public function enterPropertyKeyName(Context\PropertyKeyNameContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::propertyKeyName()}.
     *
     * @param $context The parse tree
     */
    public function exitPropertyKeyName(Context\PropertyKeyNameContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::parameter()}.
     *
     * @param $context The parse tree
     */
    public function enterParameter(Context\ParameterContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::parameter()}.
     *
     * @param $context The parse tree
     */
    public function exitParameter(Context\ParameterContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::parameterName()}.
     *
     * @param $context The parse tree
     */
    public function enterParameterName(Context\ParameterNameContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::parameterName()}.
     *
     * @param $context The parse tree
     */
    public function exitParameterName(Context\ParameterNameContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::variable()}.
     *
     * @param $context The parse tree
     */
    public function enterVariable(Context\VariableContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::variable()}.
     *
     * @param $context The parse tree
     */
    public function exitVariable(Context\VariableContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::type()}.
     *
     * @param $context The parse tree
     */
    public function enterType(Context\TypeContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::type()}.
     *
     * @param $context The parse tree
     */
    public function exitType(Context\TypeContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::typePart()}.
     *
     * @param $context The parse tree
     */
    public function enterTypePart(Context\TypePartContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::typePart()}.
     *
     * @param $context The parse tree
     */
    public function exitTypePart(Context\TypePartContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::typeName()}.
     *
     * @param $context The parse tree
     */
    public function enterTypeName(Context\TypeNameContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::typeName()}.
     *
     * @param $context The parse tree
     */
    public function exitTypeName(Context\TypeNameContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::typeNullability()}.
     *
     * @param $context The parse tree
     */
    public function enterTypeNullability(Context\TypeNullabilityContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::typeNullability()}.
     *
     * @param $context The parse tree
     */
    public function exitTypeNullability(Context\TypeNullabilityContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::typeListSuffix()}.
     *
     * @param $context The parse tree
     */
    public function enterTypeListSuffix(Context\TypeListSuffixContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::typeListSuffix()}.
     *
     * @param $context The parse tree
     */
    public function exitTypeListSuffix(Context\TypeListSuffixContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::stringLiteral()}.
     *
     * @param $context The parse tree
     */
    public function enterStringLiteral(Context\StringLiteralContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::stringLiteral()}.
     *
     * @param $context The parse tree
     */
    public function exitStringLiteral(Context\StringLiteralContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::map()}.
     *
     * @param $context The parse tree
     */
    public function enterMap(Context\MapContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::map()}.
     *
     * @param $context The parse tree
     */
    public function exitMap(Context\MapContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::symbolicNameString()}.
     *
     * @param $context The parse tree
     */
    public function enterSymbolicNameString(Context\SymbolicNameStringContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::symbolicNameString()}.
     *
     * @param $context The parse tree
     */
    public function exitSymbolicNameString(Context\SymbolicNameStringContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::escapedSymbolicNameString()}.
     *
     * @param $context The parse tree
     */
    public function enterEscapedSymbolicNameString(Context\EscapedSymbolicNameStringContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::escapedSymbolicNameString()}.
     *
     * @param $context The parse tree
     */
    public function exitEscapedSymbolicNameString(Context\EscapedSymbolicNameStringContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::unescapedSymbolicNameString()}.
     *
     * @param $context The parse tree
     */
    public function enterUnescapedSymbolicNameString(Context\UnescapedSymbolicNameStringContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::unescapedSymbolicNameString()}.
     *
     * @param $context The parse tree
     */
    public function exitUnescapedSymbolicNameString(Context\UnescapedSymbolicNameStringContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::symbolicLabelNameString()}.
     *
     * @param $context The parse tree
     */
    public function enterSymbolicLabelNameString(Context\SymbolicLabelNameStringContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::symbolicLabelNameString()}.
     *
     * @param $context The parse tree
     */
    public function exitSymbolicLabelNameString(Context\SymbolicLabelNameStringContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::unescapedLabelSymbolicNameString()}.
     *
     * @param $context The parse tree
     */
    public function enterUnescapedLabelSymbolicNameString(Context\UnescapedLabelSymbolicNameStringContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::unescapedLabelSymbolicNameString()}.
     *
     * @param $context The parse tree
     */
    public function exitUnescapedLabelSymbolicNameString(Context\UnescapedLabelSymbolicNameStringContext $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::unescapedLabelSymbolicNameString_()}.
     *
     * @param $context The parse tree
     */
    public function enterUnescapedLabelSymbolicNameString_(Context\UnescapedLabelSymbolicNameString_Context $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::unescapedLabelSymbolicNameString_()}.
     *
     * @param $context The parse tree
     */
    public function exitUnescapedLabelSymbolicNameString_(Context\UnescapedLabelSymbolicNameString_Context $context): void;

    /**
     * Enter a parse tree produced by {@see CypherPathSubset::endOfFile()}.
     *
     * @param $context The parse tree
     */
    public function enterEndOfFile(Context\EndOfFileContext $context): void;

    /**
     * Exit a parse tree produced by {@see CypherPathSubset::endOfFile()}.
     *
     * @param $context The parse tree
     */
    public function exitEndOfFile(Context\EndOfFileContext $context): void;
}
