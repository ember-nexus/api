<?php

declare(strict_types=1);

namespace App\Helper;

/**
 * The Cypher fragments which decide whether a user can read a node. They are shared by every query which checks this,
 * so the rules for conditional read access (onLabel, onParentLabel, onState, onCreatedByUser) exist only once.
 *
 * All fragments expect the variable `user` to be bound already. They walk from the element up to the user, as most
 * nodes have one parent but a lot of children.
 */
final class ReadAccessCypherHelper
{
    /**
     * Binds `path` from the element to the user, or null if the user can not read the element. The user can always read
     * the element which is the user itself.
     */
    public static function readPathMatch(string $element): string
    {
        return
            "OPTIONAL MATCH path=($element)<-[:OWNS|HAS_READ_ACCESS*0..]-()<-[:IS_IN_GROUP*0..]-(user)\n".
            "WHERE\n".
            "  user.id = $element.id\n".
            "  OR\n".
            "  ALL(relation in relationships(path) WHERE\n".
            "    type(relation) = \"IS_IN_GROUP\"\n".
            "    OR\n".
            "    type(relation) = \"OWNS\"\n".
            "    OR\n".
            "    (\n".
            "      type(relation) = \"HAS_READ_ACCESS\"\n".
            "      AND\n".
            "      (\n".
            "        relation.onLabel IS NULL\n".
            "        OR\n".
            "        relation.onLabel IN labels($element)\n".
            "      )\n".
            "      AND\n".
            "      (\n".
            "        relation.onParentLabel IS NULL\n".
            "        OR\n".
            "        relation.onParentLabel IN labels($element)\n".
            "      )\n".
            "      AND\n".
            "      (\n".
            "        relation.onState IS NULL\n".
            "        OR\n".
            "        ($element)<-[:OWNS*0..]-()-[:HAS_STATE]->(:State {id: relation.onState})\n".
            "      )\n".
            "      AND\n".
            "      (\n".
            "        relation.onCreatedByUser IS NULL\n".
            "        OR\n".
            "        $element.createdBy = user.id\n".
            "      )\n".
            "    )\n".
            "  )\n";
    }

    /**
     * Like readPathMatch(), but only follows ownership, group membership and read access relations without any
     * condition. Such access is passed on to everything the element owns.
     */
    public static function unconditionalReadPathMatch(string $element): string
    {
        return
            "OPTIONAL MATCH path=($element)<-[:OWNS|HAS_READ_ACCESS*0..]-()<-[:IS_IN_GROUP*0..]-(user)\n".
            "WHERE\n".
            "  ALL(relation IN relationships(path) WHERE\n".
            "    type(relation) = \"IS_IN_GROUP\"\n".
            "    OR\n".
            "    type(relation) = \"OWNS\"\n".
            "    OR\n".
            "    (\n".
            "      type(relation) = \"HAS_READ_ACCESS\"\n".
            "      AND relation.onLabel IS NULL\n".
            "      AND relation.onParentLabel IS NULL\n".
            "      AND relation.onState IS NULL\n".
            "      AND relation.onCreatedByUser IS NULL\n".
            "    )\n".
            "  )\n";
    }

    /**
     * Condition for a single relation `(user)-[$relation]->($element)` which is not part of a longer path, e.g. the
     * ones the index lists. Ownership and group membership always count, `HAS_READ_ACCESS` only if the element
     * satisfies its conditions (onLabel, onParentLabel, onState, onCreatedByUser); the same rules as in
     * readPathMatch(). Expects the variable `user` to be bound.
     */
    public static function directReadRelationCondition(string $relation, string $element): string
    {
        return
            "(\n".
            "  type($relation) <> \"HAS_READ_ACCESS\"\n".
            "  OR\n".
            "  (\n".
            "    ($relation.onLabel IS NULL OR $relation.onLabel IN labels($element))\n".
            "    AND ($relation.onParentLabel IS NULL OR $relation.onParentLabel IN labels($element))\n".
            "    AND (\n".
            "      $relation.onState IS NULL\n".
            "      OR ($element)<-[:OWNS*0..]-()-[:HAS_STATE]->(:State {id: $relation.onState})\n".
            "    )\n".
            "    AND ($relation.onCreatedByUser IS NULL OR $element.createdBy = user.id)\n".
            "  )\n".
            ")\n";
    }
}
