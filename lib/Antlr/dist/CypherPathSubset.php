<?php

declare(strict_types=1);

/*
 * Generated from CypherPathSubset.g4 by ANTLR 4.13.2
 */

namespace {
    use Antlr\Antlr4\Runtime\Atn\ATN;
    use Antlr\Antlr4\Runtime\Atn\ATNDeserializer;
    use Antlr\Antlr4\Runtime\Atn\ParserATNSimulator;
    use Antlr\Antlr4\Runtime\Dfa\DFA;
    use Antlr\Antlr4\Runtime\Error\Exceptions\NoViableAltException;
    use Antlr\Antlr4\Runtime\Error\Exceptions\RecognitionException;
    use Antlr\Antlr4\Runtime\Parser;
    use Antlr\Antlr4\Runtime\PredictionContexts\PredictionContextCache;
    use Antlr\Antlr4\Runtime\RuntimeMetaData;
    use Antlr\Antlr4\Runtime\Token;
    use Antlr\Antlr4\Runtime\TokenStream;
    use Antlr\Antlr4\Runtime\Vocabulary;
    use Antlr\Antlr4\Runtime\VocabularyImpl;

    final class CypherPathSubset extends Parser
    {
        public const SPACE = 1;
        public const SINGLE_LINE_COMMENT = 2;
        public const MULTI_LINE_COMMENT = 3;
        public const DECIMAL_DOUBLE = 4;
        public const UNSIGNED_DECIMAL_INTEGER = 5;
        public const UNSIGNED_HEX_INTEGER = 6;
        public const UNSIGNED_OCTAL_INTEGER = 7;
        public const STRING_LITERAL1 = 8;
        public const STRING_LITERAL2 = 9;
        public const ESCAPED_SYMBOLIC_NAME = 10;
        public const ACCESS = 11;
        public const ACTIVE = 12;
        public const ADMIN = 13;
        public const ADMINISTRATOR = 14;
        public const ALIAS = 15;
        public const ALIASES = 16;
        public const ALL_SHORTEST_PATHS = 17;
        public const ALL = 18;
        public const ALTER = 19;
        public const AND = 20;
        public const ANY = 21;
        public const ARRAY = 22;
        public const AS = 23;
        public const ASC = 24;
        public const ASCENDING = 25;
        public const ASSIGN = 26;
        public const AT = 27;
        public const AUTH = 28;
        public const BAR = 29;
        public const BINDINGS = 30;
        public const BOOL = 31;
        public const BOOLEAN = 32;
        public const BOOSTED = 33;
        public const BOTH = 34;
        public const BREAK = 35;
        public const BUILT = 36;
        public const BY = 37;
        public const CALL = 38;
        public const CASCADE = 39;
        public const CASE = 40;
        public const CHANGE = 41;
        public const CIDR = 42;
        public const COLLECT = 43;
        public const COLON = 44;
        public const COLONCOLON = 45;
        public const COMMA = 46;
        public const COMMAND = 47;
        public const COMMANDS = 48;
        public const COMPOSITE = 49;
        public const CONCURRENT = 50;
        public const CONSTRAINT = 51;
        public const CONSTRAINTS = 52;
        public const CONTAINS = 53;
        public const COPY = 54;
        public const CONTINUE = 55;
        public const COUNT = 56;
        public const CREATE = 57;
        public const CSV = 58;
        public const CURRENT = 59;
        public const DATA = 60;
        public const DATABASE = 61;
        public const DATABASES = 62;
        public const DATE = 63;
        public const DATETIME = 64;
        public const DBMS = 65;
        public const DEALLOCATE = 66;
        public const DEFAULT = 67;
        public const DEFINED = 68;
        public const DELETE = 69;
        public const DENY = 70;
        public const DESC = 71;
        public const DESCENDING = 72;
        public const DESTROY = 73;
        public const DETACH = 74;
        public const DIFFERENT = 75;
        public const DOLLAR = 76;
        public const DISTINCT = 77;
        public const DIVIDE = 78;
        public const DOT = 79;
        public const DOTDOT = 80;
        public const DOUBLEBAR = 81;
        public const DRIVER = 82;
        public const DROP = 83;
        public const DRYRUN = 84;
        public const DUMP = 85;
        public const DURATION = 86;
        public const EACH = 87;
        public const EDGE = 88;
        public const ENABLE = 89;
        public const ELEMENT = 90;
        public const ELEMENTS = 91;
        public const ELSE = 92;
        public const ENCRYPTED = 93;
        public const END = 94;
        public const ENDS = 95;
        public const EQ = 96;
        public const EXECUTABLE = 97;
        public const EXECUTE = 98;
        public const EXIST = 99;
        public const EXISTENCE = 100;
        public const EXISTS = 101;
        public const ERROR = 102;
        public const FAIL = 103;
        public const FALSE = 104;
        public const FIELDTERMINATOR = 105;
        public const FINISH = 106;
        public const FLOAT = 107;
        public const FOR = 108;
        public const FOREACH = 109;
        public const FROM = 110;
        public const FULLTEXT = 111;
        public const FUNCTION = 112;
        public const FUNCTIONS = 113;
        public const GE = 114;
        public const GRANT = 115;
        public const GRAPH = 116;
        public const GRAPHS = 117;
        public const GROUP = 118;
        public const GROUPS = 119;
        public const GT = 120;
        public const HEADERS = 121;
        public const HOME = 122;
        public const ID = 123;
        public const IF = 124;
        public const IMPERSONATE = 125;
        public const IMMUTABLE = 126;
        public const IN = 127;
        public const INDEX = 128;
        public const INDEXES = 129;
        public const INF = 130;
        public const INFINITY = 131;
        public const INSERT = 132;
        public const INT = 133;
        public const INTEGER = 134;
        public const IS = 135;
        public const JOIN = 136;
        public const KEY = 137;
        public const LABEL = 138;
        public const LABELS = 139;
        public const AMPERSAND = 140;
        public const EXCLAMATION_MARK = 141;
        public const LBRACKET = 142;
        public const LCURLY = 143;
        public const LE = 144;
        public const LEADING = 145;
        public const LIMITROWS = 146;
        public const LIST = 147;
        public const LOAD = 148;
        public const LOCAL = 149;
        public const LOOKUP = 150;
        public const LPAREN = 151;
        public const LT = 152;
        public const MANAGEMENT = 153;
        public const MAP = 154;
        public const MATCH = 155;
        public const MERGE = 156;
        public const MINUS = 157;
        public const PERCENT = 158;
        public const INVALID_NEQ = 159;
        public const NEQ = 160;
        public const NAME = 161;
        public const NAMES = 162;
        public const NAN = 163;
        public const NFC = 164;
        public const NFD = 165;
        public const NFKC = 166;
        public const NFKD = 167;
        public const NEW = 168;
        public const NODE = 169;
        public const NODETACH = 170;
        public const NODES = 171;
        public const NONE = 172;
        public const NORMALIZE = 173;
        public const NORMALIZED = 174;
        public const NOT = 175;
        public const NOTHING = 176;
        public const NOWAIT = 177;
        public const NULL = 178;
        public const OF = 179;
        public const OFFSET = 180;
        public const ON = 181;
        public const ONLY = 182;
        public const OPTIONAL = 183;
        public const OPTIONS = 184;
        public const OPTION = 185;
        public const OR = 186;
        public const ORDER = 187;
        public const PASSWORD = 188;
        public const PASSWORDS = 189;
        public const PATH = 190;
        public const PATHS = 191;
        public const PLAINTEXT = 192;
        public const PLUS = 193;
        public const PLUSEQUAL = 194;
        public const POINT = 195;
        public const POPULATED = 196;
        public const POW = 197;
        public const PRIMARY = 198;
        public const PRIMARIES = 199;
        public const PRIVILEGE = 200;
        public const PRIVILEGES = 201;
        public const PROCEDURE = 202;
        public const PROCEDURES = 203;
        public const PROPERTIES = 204;
        public const PROPERTY = 205;
        public const PROVIDER = 206;
        public const PROVIDERS = 207;
        public const QUESTION = 208;
        public const RANGE = 209;
        public const RBRACKET = 210;
        public const RCURLY = 211;
        public const READ = 212;
        public const REALLOCATE = 213;
        public const REDUCE = 214;
        public const RENAME = 215;
        public const REGEQ = 216;
        public const REL = 217;
        public const RELATIONSHIP = 218;
        public const RELATIONSHIPS = 219;
        public const REMOVE = 220;
        public const REPEATABLE = 221;
        public const REPLACE = 222;
        public const REPORT = 223;
        public const REQUIRE = 224;
        public const REQUIRED = 225;
        public const RESTRICT = 226;
        public const RETURN = 227;
        public const REVOKE = 228;
        public const ROLE = 229;
        public const ROLES = 230;
        public const ROW = 231;
        public const ROWS = 232;
        public const RPAREN = 233;
        public const SCAN = 234;
        public const SEC = 235;
        public const SECOND = 236;
        public const SECONDARY = 237;
        public const SECONDARIES = 238;
        public const SECONDS = 239;
        public const SEEK = 240;
        public const SEMICOLON = 241;
        public const SERVER = 242;
        public const SERVERS = 243;
        public const SET = 244;
        public const SETTING = 245;
        public const SETTINGS = 246;
        public const SHORTEST_PATH = 247;
        public const SHORTEST = 248;
        public const SHOW = 249;
        public const SIGNED = 250;
        public const SINGLE = 251;
        public const SKIPROWS = 252;
        public const START = 253;
        public const STARTS = 254;
        public const STATUS = 255;
        public const STOP = 256;
        public const STRING = 257;
        public const SUPPORTED = 258;
        public const SUSPENDED = 259;
        public const TARGET = 260;
        public const TERMINATE = 261;
        public const TEXT = 262;
        public const THEN = 263;
        public const TIME = 264;
        public const TIMES = 265;
        public const TIMESTAMP = 266;
        public const TIMEZONE = 267;
        public const TO = 268;
        public const TOPOLOGY = 269;
        public const TRAILING = 270;
        public const TRANSACTION = 271;
        public const TRANSACTIONS = 272;
        public const TRAVERSE = 273;
        public const TRIM = 274;
        public const TRUE = 275;
        public const TYPE = 276;
        public const TYPED = 277;
        public const TYPES = 278;
        public const UNION = 279;
        public const UNIQUE = 280;
        public const UNIQUENESS = 281;
        public const UNWIND = 282;
        public const URL = 283;
        public const USE = 284;
        public const USER = 285;
        public const USERS = 286;
        public const USING = 287;
        public const VALUE = 288;
        public const VARCHAR = 289;
        public const VECTOR = 290;
        public const VERTEX = 291;
        public const WAIT = 292;
        public const WHEN = 293;
        public const WHERE = 294;
        public const WITH = 295;
        public const WITHOUT = 296;
        public const WRITE = 297;
        public const XOR = 298;
        public const YIELD = 299;
        public const ZONE = 300;
        public const ZONED = 301;
        public const IDENTIFIER = 302;
        public const EXTENDED_IDENTIFIER = 303;
        public const ARROW_LINE = 304;
        public const ARROW_LEFT_HEAD = 305;
        public const ARROW_RIGHT_HEAD = 306;
        public const ErrorChar = 307;

        public const RULE_statements = 0;
        public const RULE_statement = 1;
        public const RULE_regularQuery = 2;
        public const RULE_singleQuery = 3;
        public const RULE_returnClause = 4;
        public const RULE_returnBody = 5;
        public const RULE_returnItem = 6;
        public const RULE_returnItems = 7;
        public const RULE_orderItem = 8;
        public const RULE_ascToken = 9;
        public const RULE_descToken = 10;
        public const RULE_orderBy = 11;
        public const RULE_skip = 12;
        public const RULE_limit = 13;
        public const RULE_whereClause = 14;
        public const RULE_matchClause = 15;
        public const RULE_unwindClause = 16;
        public const RULE_patternList = 17;
        public const RULE_pattern = 18;
        public const RULE_quantifier = 19;
        public const RULE_anonymousPattern = 20;
        public const RULE_patternElement = 21;
        public const RULE_nodePattern = 22;
        public const RULE_parenthesizedPath = 23;
        public const RULE_properties = 24;
        public const RULE_relationshipPattern = 25;
        public const RULE_leftArrow = 26;
        public const RULE_arrowLine = 27;
        public const RULE_rightArrow = 28;
        public const RULE_pathLength = 29;
        public const RULE_labelExpression = 30;
        public const RULE_labelExpression4 = 31;
        public const RULE_labelExpression4Is = 32;
        public const RULE_labelExpression3 = 33;
        public const RULE_labelExpression3Is = 34;
        public const RULE_labelExpression2 = 35;
        public const RULE_labelExpression2Is = 36;
        public const RULE_labelExpression1 = 37;
        public const RULE_labelExpression1Is = 38;
        public const RULE_expression = 39;
        public const RULE_expression11 = 40;
        public const RULE_expression10 = 41;
        public const RULE_expression9 = 42;
        public const RULE_expression8 = 43;
        public const RULE_expression7 = 44;
        public const RULE_comparisonExpression6 = 45;
        public const RULE_normalForm = 46;
        public const RULE_expression6 = 47;
        public const RULE_expression5 = 48;
        public const RULE_expression4 = 49;
        public const RULE_expression3 = 50;
        public const RULE_expression2 = 51;
        public const RULE_postFix = 52;
        public const RULE_property = 53;
        public const RULE_expression1 = 54;
        public const RULE_literal = 55;
        public const RULE_parenthesizedExpression = 56;
        public const RULE_numberLiteral = 57;
        public const RULE_propertyKeyName = 58;
        public const RULE_parameter = 59;
        public const RULE_parameterName = 60;
        public const RULE_variable = 61;
        public const RULE_type = 62;
        public const RULE_typePart = 63;
        public const RULE_typeName = 64;
        public const RULE_typeNullability = 65;
        public const RULE_typeListSuffix = 66;
        public const RULE_stringLiteral = 67;
        public const RULE_map = 68;
        public const RULE_symbolicNameString = 69;
        public const RULE_escapedSymbolicNameString = 70;
        public const RULE_unescapedSymbolicNameString = 71;
        public const RULE_symbolicLabelNameString = 72;
        public const RULE_unescapedLabelSymbolicNameString = 73;
        public const RULE_unescapedLabelSymbolicNameString_ = 74;
        public const RULE_endOfFile = 75;

        /**
         * @var array<string>
         */
        public const RULE_NAMES = [
            'statements', 'statement', 'regularQuery', 'singleQuery', 'returnClause',
            'returnBody', 'returnItem', 'returnItems', 'orderItem', 'ascToken', 'descToken',
            'orderBy', 'skip', 'limit', 'whereClause', 'matchClause', 'unwindClause',
            'patternList', 'pattern', 'quantifier', 'anonymousPattern', 'patternElement',
            'nodePattern', 'parenthesizedPath', 'properties', 'relationshipPattern',
            'leftArrow', 'arrowLine', 'rightArrow', 'pathLength', 'labelExpression',
            'labelExpression4', 'labelExpression4Is', 'labelExpression3', 'labelExpression3Is',
            'labelExpression2', 'labelExpression2Is', 'labelExpression1', 'labelExpression1Is',
            'expression', 'expression11', 'expression10', 'expression9', 'expression8',
            'expression7', 'comparisonExpression6', 'normalForm', 'expression6',
            'expression5', 'expression4', 'expression3', 'expression2', 'postFix',
            'property', 'expression1', 'literal', 'parenthesizedExpression', 'numberLiteral',
            'propertyKeyName', 'parameter', 'parameterName', 'variable', 'type',
            'typePart', 'typeName', 'typeNullability', 'typeListSuffix', 'stringLiteral',
            'map', 'symbolicNameString', 'escapedSymbolicNameString', 'unescapedSymbolicNameString',
            'symbolicLabelNameString', 'unescapedLabelSymbolicNameString', 'unescapedLabelSymbolicNameString_',
            'endOfFile',
        ];

        /**
         * @var array<string|null>
         */
        private const LITERAL_NAMES = [
            null, null, null, null, null, null, null, null, null, null, null,
            null, null, null, null, null, null, null, null, null, null, null,
            null, null, null, null, null, null, null, "'|'", null, null, null,
            null, null, null, null, null, null, null, null, null, null, null,
            "':'", "'::'", "','", null, null, null, null, null, null, null, null,
            null, null, null, null, null, null, null, null, null, null, null,
            null, null, null, null, null, null, null, null, null, null, "'\$'",
            null, "'/'", "'.'", "'..'", "'||'", null, null, null, null, null,
            null, null, null, null, null, null, null, null, null, "'='", null,
            null, null, null, null, null, null, null, null, null, null, null,
            null, null, null, null, null, "'>='", null, null, null, null, null,
            "'>'", null, null, null, null, null, null, null, null, null, null,
            null, null, null, null, null, null, null, null, null, "'&'", "'!'",
            "'['", "'{'", "'<='", null, null, null, null, null, null, "'('", "'<'",
            null, null, null, null, "'-'", "'%'", "'!='", "'<>'", null, null,
            null, null, null, null, null, null, null, null, null, null, null,
            null, null, null, null, null, null, null, null, null, null, null,
            null, null, null, null, null, null, null, null, "'+'", "'+='", null,
            null, "'^'", null, null, null, null, null, null, null, null, null,
            null, "'?'", null, "']'", "'}'", null, null, null, null, "'=~'", null,
            null, null, null, null, null, null, null, null, null, null, null,
            null, null, null, null, "')'", null, null, null, null, null, null,
            null, "';'", null, null, null, null, null, null, null, null, null,
            null, null, null, null, null, null, null, null, null, null, null,
            null, null, null, "'*'",
        ];

        /**
         * @var array<string>
         */
        private const SYMBOLIC_NAMES = [
            null, 'SPACE', 'SINGLE_LINE_COMMENT', 'MULTI_LINE_COMMENT', 'DECIMAL_DOUBLE',
            'UNSIGNED_DECIMAL_INTEGER', 'UNSIGNED_HEX_INTEGER', 'UNSIGNED_OCTAL_INTEGER',
            'STRING_LITERAL1', 'STRING_LITERAL2', 'ESCAPED_SYMBOLIC_NAME', 'ACCESS',
            'ACTIVE', 'ADMIN', 'ADMINISTRATOR', 'ALIAS', 'ALIASES', 'ALL_SHORTEST_PATHS',
            'ALL', 'ALTER', 'AND', 'ANY', 'ARRAY', 'AS', 'ASC', 'ASCENDING', 'ASSIGN',
            'AT', 'AUTH', 'BAR', 'BINDINGS', 'BOOL', 'BOOLEAN', 'BOOSTED', 'BOTH',
            'BREAK', 'BUILT', 'BY', 'CALL', 'CASCADE', 'CASE', 'CHANGE', 'CIDR',
            'COLLECT', 'COLON', 'COLONCOLON', 'COMMA', 'COMMAND', 'COMMANDS',
            'COMPOSITE', 'CONCURRENT', 'CONSTRAINT', 'CONSTRAINTS', 'CONTAINS',
            'COPY', 'CONTINUE', 'COUNT', 'CREATE', 'CSV', 'CURRENT', 'DATA', 'DATABASE',
            'DATABASES', 'DATE', 'DATETIME', 'DBMS', 'DEALLOCATE', 'DEFAULT',
            'DEFINED', 'DELETE', 'DENY', 'DESC', 'DESCENDING', 'DESTROY', 'DETACH',
            'DIFFERENT', 'DOLLAR', 'DISTINCT', 'DIVIDE', 'DOT', 'DOTDOT', 'DOUBLEBAR',
            'DRIVER', 'DROP', 'DRYRUN', 'DUMP', 'DURATION', 'EACH', 'EDGE', 'ENABLE',
            'ELEMENT', 'ELEMENTS', 'ELSE', 'ENCRYPTED', 'END', 'ENDS', 'EQ', 'EXECUTABLE',
            'EXECUTE', 'EXIST', 'EXISTENCE', 'EXISTS', 'ERROR', 'FAIL', 'FALSE',
            'FIELDTERMINATOR', 'FINISH', 'FLOAT', 'FOR', 'FOREACH', 'FROM', 'FULLTEXT',
            'FUNCTION', 'FUNCTIONS', 'GE', 'GRANT', 'GRAPH', 'GRAPHS', 'GROUP',
            'GROUPS', 'GT', 'HEADERS', 'HOME', 'ID', 'IF', 'IMPERSONATE', 'IMMUTABLE',
            'IN', 'INDEX', 'INDEXES', 'INF', 'INFINITY', 'INSERT', 'INT', 'INTEGER',
            'IS', 'JOIN', 'KEY', 'LABEL', 'LABELS', 'AMPERSAND', 'EXCLAMATION_MARK',
            'LBRACKET', 'LCURLY', 'LE', 'LEADING', 'LIMITROWS', 'LIST', 'LOAD',
            'LOCAL', 'LOOKUP', 'LPAREN', 'LT', 'MANAGEMENT', 'MAP', 'MATCH', 'MERGE',
            'MINUS', 'PERCENT', 'INVALID_NEQ', 'NEQ', 'NAME', 'NAMES', 'NAN',
            'NFC', 'NFD', 'NFKC', 'NFKD', 'NEW', 'NODE', 'NODETACH', 'NODES',
            'NONE', 'NORMALIZE', 'NORMALIZED', 'NOT', 'NOTHING', 'NOWAIT', 'NULL',
            'OF', 'OFFSET', 'ON', 'ONLY', 'OPTIONAL', 'OPTIONS', 'OPTION', 'OR',
            'ORDER', 'PASSWORD', 'PASSWORDS', 'PATH', 'PATHS', 'PLAINTEXT', 'PLUS',
            'PLUSEQUAL', 'POINT', 'POPULATED', 'POW', 'PRIMARY', 'PRIMARIES',
            'PRIVILEGE', 'PRIVILEGES', 'PROCEDURE', 'PROCEDURES', 'PROPERTIES',
            'PROPERTY', 'PROVIDER', 'PROVIDERS', 'QUESTION', 'RANGE', 'RBRACKET',
            'RCURLY', 'READ', 'REALLOCATE', 'REDUCE', 'RENAME', 'REGEQ', 'REL',
            'RELATIONSHIP', 'RELATIONSHIPS', 'REMOVE', 'REPEATABLE', 'REPLACE',
            'REPORT', 'REQUIRE', 'REQUIRED', 'RESTRICT', 'RETURN', 'REVOKE', 'ROLE',
            'ROLES', 'ROW', 'ROWS', 'RPAREN', 'SCAN', 'SEC', 'SECOND', 'SECONDARY',
            'SECONDARIES', 'SECONDS', 'SEEK', 'SEMICOLON', 'SERVER', 'SERVERS',
            'SET', 'SETTING', 'SETTINGS', 'SHORTEST_PATH', 'SHORTEST', 'SHOW',
            'SIGNED', 'SINGLE', 'SKIPROWS', 'START', 'STARTS', 'STATUS', 'STOP',
            'STRING', 'SUPPORTED', 'SUSPENDED', 'TARGET', 'TERMINATE', 'TEXT',
            'THEN', 'TIME', 'TIMES', 'TIMESTAMP', 'TIMEZONE', 'TO', 'TOPOLOGY',
            'TRAILING', 'TRANSACTION', 'TRANSACTIONS', 'TRAVERSE', 'TRIM', 'TRUE',
            'TYPE', 'TYPED', 'TYPES', 'UNION', 'UNIQUE', 'UNIQUENESS', 'UNWIND',
            'URL', 'USE', 'USER', 'USERS', 'USING', 'VALUE', 'VARCHAR', 'VECTOR',
            'VERTEX', 'WAIT', 'WHEN', 'WHERE', 'WITH', 'WITHOUT', 'WRITE', 'XOR',
            'YIELD', 'ZONE', 'ZONED', 'IDENTIFIER', 'EXTENDED_IDENTIFIER', 'ARROW_LINE',
            'ARROW_LEFT_HEAD', 'ARROW_RIGHT_HEAD', 'ErrorChar',
        ];

        private const SERIALIZED_ATN =
            [4, 1, 307, 738, 2, 0, 7, 0, 2, 1, 7, 1, 2, 2, 7, 2, 2, 3, 7, 3, 2, 4,
                7, 4, 2, 5, 7, 5, 2, 6, 7, 6, 2, 7, 7, 7, 2, 8, 7, 8, 2, 9, 7, 9,
                2, 10, 7, 10, 2, 11, 7, 11, 2, 12, 7, 12, 2, 13, 7, 13, 2, 14, 7,
                14, 2, 15, 7, 15, 2, 16, 7, 16, 2, 17, 7, 17, 2, 18, 7, 18, 2, 19,
                7, 19, 2, 20, 7, 20, 2, 21, 7, 21, 2, 22, 7, 22, 2, 23, 7, 23, 2,
                24, 7, 24, 2, 25, 7, 25, 2, 26, 7, 26, 2, 27, 7, 27, 2, 28, 7, 28,
                2, 29, 7, 29, 2, 30, 7, 30, 2, 31, 7, 31, 2, 32, 7, 32, 2, 33, 7,
                33, 2, 34, 7, 34, 2, 35, 7, 35, 2, 36, 7, 36, 2, 37, 7, 37, 2, 38,
                7, 38, 2, 39, 7, 39, 2, 40, 7, 40, 2, 41, 7, 41, 2, 42, 7, 42, 2,
                43, 7, 43, 2, 44, 7, 44, 2, 45, 7, 45, 2, 46, 7, 46, 2, 47, 7, 47,
                2, 48, 7, 48, 2, 49, 7, 49, 2, 50, 7, 50, 2, 51, 7, 51, 2, 52, 7,
                52, 2, 53, 7, 53, 2, 54, 7, 54, 2, 55, 7, 55, 2, 56, 7, 56, 2, 57,
                7, 57, 2, 58, 7, 58, 2, 59, 7, 59, 2, 60, 7, 60, 2, 61, 7, 61, 2,
                62, 7, 62, 2, 63, 7, 63, 2, 64, 7, 64, 2, 65, 7, 65, 2, 66, 7, 66,
                2, 67, 7, 67, 2, 68, 7, 68, 2, 69, 7, 69, 2, 70, 7, 70, 2, 71, 7,
                71, 2, 72, 7, 72, 2, 73, 7, 73, 2, 74, 7, 74, 2, 75, 7, 75, 1, 0,
                1, 0, 3, 0, 155, 8, 0, 1, 0, 1, 0, 1, 1, 1, 1, 1, 2, 1, 2, 1, 3, 3,
                3, 164, 8, 3, 1, 3, 1, 3, 1, 3, 1, 4, 1, 4, 1, 4, 1, 5, 3, 5, 173,
                8, 5, 1, 5, 1, 5, 3, 5, 177, 8, 5, 1, 5, 3, 5, 180, 8, 5, 1, 5, 3,
                5, 183, 8, 5, 1, 6, 1, 6, 1, 7, 1, 7, 1, 8, 1, 8, 1, 8, 3, 8, 192,
                8, 8, 1, 9, 1, 9, 1, 10, 1, 10, 1, 11, 1, 11, 1, 11, 1, 11, 1, 11,
                5, 11, 203, 8, 11, 10, 11, 12, 11, 206, 9, 11, 1, 12, 1, 12, 1, 12,
                1, 13, 1, 13, 1, 13, 1, 14, 1, 14, 1, 14, 1, 15, 1, 15, 1, 15, 3,
                15, 220, 8, 15, 1, 16, 1, 16, 1, 16, 1, 16, 1, 16, 1, 17, 1, 17, 1,
                18, 1, 18, 1, 18, 3, 18, 232, 8, 18, 1, 18, 1, 18, 1, 19, 1, 19, 1,
                19, 1, 19, 1, 19, 3, 19, 241, 8, 19, 1, 19, 1, 19, 3, 19, 245, 8,
                19, 1, 19, 1, 19, 1, 19, 3, 19, 250, 8, 19, 1, 20, 1, 20, 1, 21, 1,
                21, 1, 21, 3, 21, 257, 8, 21, 1, 21, 1, 21, 5, 21, 261, 8, 21, 10,
                21, 12, 21, 264, 9, 21, 1, 21, 4, 21, 267, 8, 21, 11, 21, 12, 21,
                268, 1, 22, 1, 22, 3, 22, 273, 8, 22, 1, 22, 3, 22, 276, 8, 22, 1,
                22, 3, 22, 279, 8, 22, 1, 22, 1, 22, 3, 22, 283, 8, 22, 1, 22, 1,
                22, 1, 23, 1, 23, 1, 23, 1, 23, 3, 23, 291, 8, 23, 1, 23, 1, 23, 3,
                23, 295, 8, 23, 1, 24, 1, 24, 3, 24, 299, 8, 24, 1, 25, 3, 25, 302,
                8, 25, 1, 25, 1, 25, 1, 25, 3, 25, 307, 8, 25, 1, 25, 3, 25, 310,
                8, 25, 1, 25, 3, 25, 313, 8, 25, 1, 25, 3, 25, 316, 8, 25, 1, 25,
                1, 25, 3, 25, 320, 8, 25, 1, 25, 3, 25, 323, 8, 25, 1, 25, 1, 25,
                3, 25, 327, 8, 25, 1, 26, 1, 26, 1, 27, 1, 27, 1, 28, 1, 28, 1, 29,
                1, 29, 3, 29, 337, 8, 29, 1, 29, 1, 29, 3, 29, 341, 8, 29, 1, 29,
                3, 29, 344, 8, 29, 1, 30, 1, 30, 1, 30, 1, 30, 3, 30, 350, 8, 30,
                1, 31, 1, 31, 1, 31, 3, 31, 355, 8, 31, 1, 31, 5, 31, 358, 8, 31,
                10, 31, 12, 31, 361, 9, 31, 1, 32, 1, 32, 1, 32, 3, 32, 366, 8, 32,
                1, 32, 5, 32, 369, 8, 32, 10, 32, 12, 32, 372, 9, 32, 1, 33, 1, 33,
                1, 33, 5, 33, 377, 8, 33, 10, 33, 12, 33, 380, 9, 33, 1, 34, 1, 34,
                1, 34, 5, 34, 385, 8, 34, 10, 34, 12, 34, 388, 9, 34, 1, 35, 5, 35,
                391, 8, 35, 10, 35, 12, 35, 394, 9, 35, 1, 35, 1, 35, 1, 36, 5, 36,
                399, 8, 36, 10, 36, 12, 36, 402, 9, 36, 1, 36, 1, 36, 1, 37, 1, 37,
                1, 37, 1, 37, 1, 37, 1, 37, 3, 37, 412, 8, 37, 1, 38, 1, 38, 1, 38,
                1, 38, 1, 38, 1, 38, 3, 38, 420, 8, 38, 1, 39, 1, 39, 1, 39, 5, 39,
                425, 8, 39, 10, 39, 12, 39, 428, 9, 39, 1, 40, 1, 40, 1, 40, 5, 40,
                433, 8, 40, 10, 40, 12, 40, 436, 9, 40, 1, 41, 1, 41, 1, 41, 5, 41,
                441, 8, 41, 10, 41, 12, 41, 444, 9, 41, 1, 42, 5, 42, 447, 8, 42,
                10, 42, 12, 42, 450, 9, 42, 1, 42, 1, 42, 1, 43, 1, 43, 1, 43, 5,
                43, 457, 8, 43, 10, 43, 12, 43, 460, 9, 43, 1, 44, 1, 44, 3, 44, 464,
                8, 44, 1, 45, 1, 45, 1, 45, 1, 45, 1, 45, 1, 45, 1, 45, 3, 45, 473,
                8, 45, 1, 45, 1, 45, 1, 45, 3, 45, 478, 8, 45, 1, 45, 1, 45, 1, 45,
                3, 45, 483, 8, 45, 1, 45, 1, 45, 3, 45, 487, 8, 45, 1, 45, 1, 45,
                1, 45, 3, 45, 492, 8, 45, 1, 45, 3, 45, 495, 8, 45, 1, 45, 3, 45,
                498, 8, 45, 1, 46, 1, 46, 1, 47, 1, 47, 1, 47, 5, 47, 505, 8, 47,
                10, 47, 12, 47, 508, 9, 47, 1, 48, 1, 48, 1, 48, 5, 48, 513, 8, 48,
                10, 48, 12, 48, 516, 9, 48, 1, 49, 1, 49, 1, 49, 5, 49, 521, 8, 49,
                10, 49, 12, 49, 524, 9, 49, 1, 50, 1, 50, 1, 50, 3, 50, 529, 8, 50,
                1, 51, 1, 51, 5, 51, 533, 8, 51, 10, 51, 12, 51, 536, 9, 51, 1, 52,
                1, 52, 1, 52, 1, 52, 1, 52, 1, 52, 1, 52, 1, 52, 3, 52, 546, 8, 52,
                1, 52, 1, 52, 3, 52, 550, 8, 52, 1, 52, 3, 52, 553, 8, 52, 1, 53,
                1, 53, 1, 53, 1, 54, 1, 54, 1, 54, 1, 54, 3, 54, 562, 8, 54, 1, 55,
                1, 55, 1, 55, 1, 55, 1, 55, 1, 55, 1, 55, 1, 55, 1, 55, 3, 55, 573,
                8, 55, 1, 56, 1, 56, 1, 56, 1, 56, 1, 57, 3, 57, 580, 8, 57, 1, 57,
                1, 57, 1, 58, 1, 58, 1, 59, 1, 59, 1, 59, 1, 60, 1, 60, 1, 60, 1,
                60, 3, 60, 593, 8, 60, 1, 61, 1, 61, 1, 62, 1, 62, 1, 62, 5, 62, 600,
                8, 62, 10, 62, 12, 62, 603, 9, 62, 1, 63, 1, 63, 3, 63, 607, 8, 63,
                1, 63, 5, 63, 610, 8, 63, 10, 63, 12, 63, 613, 9, 63, 1, 64, 1, 64,
                1, 64, 1, 64, 1, 64, 1, 64, 1, 64, 1, 64, 3, 64, 623, 8, 64, 1, 64,
                1, 64, 1, 64, 1, 64, 1, 64, 1, 64, 1, 64, 1, 64, 1, 64, 1, 64, 1,
                64, 1, 64, 3, 64, 637, 8, 64, 1, 64, 1, 64, 1, 64, 1, 64, 1, 64, 3,
                64, 644, 8, 64, 1, 64, 1, 64, 1, 64, 1, 64, 1, 64, 1, 64, 1, 64, 1,
                64, 1, 64, 1, 64, 1, 64, 1, 64, 1, 64, 1, 64, 1, 64, 1, 64, 1, 64,
                1, 64, 1, 64, 1, 64, 1, 64, 1, 64, 1, 64, 1, 64, 1, 64, 3, 64, 671,
                8, 64, 1, 64, 1, 64, 1, 64, 1, 64, 1, 64, 3, 64, 678, 8, 64, 3, 64,
                680, 8, 64, 1, 65, 1, 65, 1, 65, 3, 65, 685, 8, 65, 1, 66, 1, 66,
                3, 66, 689, 8, 66, 1, 67, 1, 67, 1, 68, 1, 68, 1, 68, 1, 68, 1, 68,
                1, 68, 1, 68, 1, 68, 1, 68, 5, 68, 702, 8, 68, 10, 68, 12, 68, 705,
                9, 68, 3, 68, 707, 8, 68, 1, 68, 1, 68, 1, 69, 1, 69, 3, 69, 713,
                8, 69, 1, 70, 1, 70, 1, 71, 1, 71, 1, 71, 1, 71, 1, 71, 1, 71, 1,
                71, 1, 71, 1, 71, 3, 71, 726, 8, 71, 1, 72, 1, 72, 3, 72, 730, 8,
                72, 1, 73, 1, 73, 1, 74, 1, 74, 1, 75, 1, 75, 1, 75, 0, 0, 76, 0,
                2, 4, 6, 8, 10, 12, 14, 16, 18, 20, 22, 24, 26, 28, 30, 32, 34, 36,
                38, 40, 42, 44, 46, 48, 50, 52, 54, 56, 58, 60, 62, 64, 66, 68, 70,
                72, 74, 76, 78, 80, 82, 84, 86, 88, 90, 92, 94, 96, 98, 100, 102,
                104, 106, 108, 110, 112, 114, 116, 118, 120, 122, 124, 126, 128, 130,
                132, 134, 136, 138, 140, 142, 144, 146, 148, 150, 0, 19, 1, 0, 24,
                25, 1, 0, 71, 72, 2, 0, 180, 180, 252, 252, 2, 0, 152, 152, 305, 305,
                2, 0, 157, 157, 304, 304, 2, 0, 120, 120, 306, 306, 2, 0, 44, 44,
                140, 140, 6, 0, 96, 96, 114, 114, 120, 120, 144, 144, 152, 152, 159,
                160, 2, 0, 45, 45, 277, 277, 1, 0, 164, 167, 3, 0, 81, 81, 157, 157,
                193, 193, 3, 0, 78, 78, 158, 158, 265, 265, 2, 0, 157, 157, 193, 193,
                1, 0, 4, 7, 2, 0, 64, 64, 264, 264, 1, 0, 295, 296, 2, 0, 22, 22,
                147, 147, 1, 0, 8, 9, 23, 0, 11, 28, 30, 43, 47, 75, 77, 77, 82, 95,
                97, 113, 115, 119, 121, 139, 145, 150, 153, 156, 161, 163, 168, 173,
                176, 177, 179, 192, 195, 196, 198, 207, 209, 209, 212, 215, 217, 232,
                234, 240, 242, 264, 266, 276, 278, 302, 810, 0, 152, 1, 0, 0, 0, 2,
                158, 1, 0, 0, 0, 4, 160, 1, 0, 0, 0, 6, 163, 1, 0, 0, 0, 8, 168, 1,
                0, 0, 0, 10, 172, 1, 0, 0, 0, 12, 184, 1, 0, 0, 0, 14, 186, 1, 0,
                0, 0, 16, 188, 1, 0, 0, 0, 18, 193, 1, 0, 0, 0, 20, 195, 1, 0, 0,
                0, 22, 197, 1, 0, 0, 0, 24, 207, 1, 0, 0, 0, 26, 210, 1, 0, 0, 0,
                28, 213, 1, 0, 0, 0, 30, 216, 1, 0, 0, 0, 32, 221, 1, 0, 0, 0, 34,
                226, 1, 0, 0, 0, 36, 231, 1, 0, 0, 0, 38, 249, 1, 0, 0, 0, 40, 251,
                1, 0, 0, 0, 42, 266, 1, 0, 0, 0, 44, 270, 1, 0, 0, 0, 46, 286, 1,
                0, 0, 0, 48, 298, 1, 0, 0, 0, 50, 301, 1, 0, 0, 0, 52, 328, 1, 0,
                0, 0, 54, 330, 1, 0, 0, 0, 56, 332, 1, 0, 0, 0, 58, 334, 1, 0, 0,
                0, 60, 349, 1, 0, 0, 0, 62, 351, 1, 0, 0, 0, 64, 362, 1, 0, 0, 0,
                66, 373, 1, 0, 0, 0, 68, 381, 1, 0, 0, 0, 70, 392, 1, 0, 0, 0, 72,
                400, 1, 0, 0, 0, 74, 411, 1, 0, 0, 0, 76, 419, 1, 0, 0, 0, 78, 421,
                1, 0, 0, 0, 80, 429, 1, 0, 0, 0, 82, 437, 1, 0, 0, 0, 84, 448, 1,
                0, 0, 0, 86, 453, 1, 0, 0, 0, 88, 461, 1, 0, 0, 0, 90, 497, 1, 0,
                0, 0, 92, 499, 1, 0, 0, 0, 94, 501, 1, 0, 0, 0, 96, 509, 1, 0, 0,
                0, 98, 517, 1, 0, 0, 0, 100, 528, 1, 0, 0, 0, 102, 530, 1, 0, 0, 0,
                104, 552, 1, 0, 0, 0, 106, 554, 1, 0, 0, 0, 108, 561, 1, 0, 0, 0,
                110, 572, 1, 0, 0, 0, 112, 574, 1, 0, 0, 0, 114, 579, 1, 0, 0, 0,
                116, 583, 1, 0, 0, 0, 118, 585, 1, 0, 0, 0, 120, 592, 1, 0, 0, 0,
                122, 594, 1, 0, 0, 0, 124, 596, 1, 0, 0, 0, 126, 604, 1, 0, 0, 0,
                128, 679, 1, 0, 0, 0, 130, 684, 1, 0, 0, 0, 132, 686, 1, 0, 0, 0,
                134, 690, 1, 0, 0, 0, 136, 692, 1, 0, 0, 0, 138, 712, 1, 0, 0, 0,
                140, 714, 1, 0, 0, 0, 142, 725, 1, 0, 0, 0, 144, 729, 1, 0, 0, 0,
                146, 731, 1, 0, 0, 0, 148, 733, 1, 0, 0, 0, 150, 735, 1, 0, 0, 0,
                152, 154, 3, 2, 1, 0, 153, 155, 5, 241, 0, 0, 154, 153, 1, 0, 0, 0,
                154, 155, 1, 0, 0, 0, 155, 156, 1, 0, 0, 0, 156, 157, 5, 0, 0, 1,
                157, 1, 1, 0, 0, 0, 158, 159, 3, 4, 2, 0, 159, 3, 1, 0, 0, 0, 160,
                161, 3, 6, 3, 0, 161, 5, 1, 0, 0, 0, 162, 164, 3, 32, 16, 0, 163,
                162, 1, 0, 0, 0, 163, 164, 1, 0, 0, 0, 164, 165, 1, 0, 0, 0, 165,
                166, 3, 30, 15, 0, 166, 167, 3, 8, 4, 0, 167, 7, 1, 0, 0, 0, 168,
                169, 5, 227, 0, 0, 169, 170, 3, 10, 5, 0, 170, 9, 1, 0, 0, 0, 171,
                173, 5, 77, 0, 0, 172, 171, 1, 0, 0, 0, 172, 173, 1, 0, 0, 0, 173,
                174, 1, 0, 0, 0, 174, 176, 3, 14, 7, 0, 175, 177, 3, 22, 11, 0, 176,
                175, 1, 0, 0, 0, 176, 177, 1, 0, 0, 0, 177, 179, 1, 0, 0, 0, 178,
                180, 3, 24, 12, 0, 179, 178, 1, 0, 0, 0, 179, 180, 1, 0, 0, 0, 180,
                182, 1, 0, 0, 0, 181, 183, 3, 26, 13, 0, 182, 181, 1, 0, 0, 0, 182,
                183, 1, 0, 0, 0, 183, 11, 1, 0, 0, 0, 184, 185, 3, 122, 61, 0, 185,
                13, 1, 0, 0, 0, 186, 187, 3, 12, 6, 0, 187, 15, 1, 0, 0, 0, 188, 191,
                3, 78, 39, 0, 189, 192, 3, 18, 9, 0, 190, 192, 3, 20, 10, 0, 191,
                189, 1, 0, 0, 0, 191, 190, 1, 0, 0, 0, 191, 192, 1, 0, 0, 0, 192,
                17, 1, 0, 0, 0, 193, 194, 7, 0, 0, 0, 194, 19, 1, 0, 0, 0, 195, 196,
                7, 1, 0, 0, 196, 21, 1, 0, 0, 0, 197, 198, 5, 187, 0, 0, 198, 199,
                5, 37, 0, 0, 199, 204, 3, 16, 8, 0, 200, 201, 5, 46, 0, 0, 201, 203,
                3, 16, 8, 0, 202, 200, 1, 0, 0, 0, 203, 206, 1, 0, 0, 0, 204, 202,
                1, 0, 0, 0, 204, 205, 1, 0, 0, 0, 205, 23, 1, 0, 0, 0, 206, 204, 1,
                0, 0, 0, 207, 208, 7, 2, 0, 0, 208, 209, 3, 78, 39, 0, 209, 25, 1,
                0, 0, 0, 210, 211, 5, 146, 0, 0, 211, 212, 3, 78, 39, 0, 212, 27,
                1, 0, 0, 0, 213, 214, 5, 294, 0, 0, 214, 215, 3, 78, 39, 0, 215, 29,
                1, 0, 0, 0, 216, 217, 5, 155, 0, 0, 217, 219, 3, 34, 17, 0, 218, 220,
                3, 28, 14, 0, 219, 218, 1, 0, 0, 0, 219, 220, 1, 0, 0, 0, 220, 31,
                1, 0, 0, 0, 221, 222, 5, 282, 0, 0, 222, 223, 3, 78, 39, 0, 223, 224,
                5, 23, 0, 0, 224, 225, 3, 122, 61, 0, 225, 33, 1, 0, 0, 0, 226, 227,
                3, 36, 18, 0, 227, 35, 1, 0, 0, 0, 228, 229, 3, 122, 61, 0, 229, 230,
                5, 96, 0, 0, 230, 232, 1, 0, 0, 0, 231, 228, 1, 0, 0, 0, 231, 232,
                1, 0, 0, 0, 232, 233, 1, 0, 0, 0, 233, 234, 3, 40, 20, 0, 234, 37,
                1, 0, 0, 0, 235, 236, 5, 143, 0, 0, 236, 237, 5, 5, 0, 0, 237, 250,
                5, 211, 0, 0, 238, 240, 5, 143, 0, 0, 239, 241, 5, 5, 0, 0, 240, 239,
                1, 0, 0, 0, 240, 241, 1, 0, 0, 0, 241, 242, 1, 0, 0, 0, 242, 244,
                5, 46, 0, 0, 243, 245, 5, 5, 0, 0, 244, 243, 1, 0, 0, 0, 244, 245,
                1, 0, 0, 0, 245, 246, 1, 0, 0, 0, 246, 250, 5, 211, 0, 0, 247, 250,
                5, 193, 0, 0, 248, 250, 5, 265, 0, 0, 249, 235, 1, 0, 0, 0, 249, 238,
                1, 0, 0, 0, 249, 247, 1, 0, 0, 0, 249, 248, 1, 0, 0, 0, 250, 39, 1,
                0, 0, 0, 251, 252, 3, 42, 21, 0, 252, 41, 1, 0, 0, 0, 253, 262, 3,
                44, 22, 0, 254, 256, 3, 50, 25, 0, 255, 257, 3, 38, 19, 0, 256, 255,
                1, 0, 0, 0, 256, 257, 1, 0, 0, 0, 257, 258, 1, 0, 0, 0, 258, 259,
                3, 44, 22, 0, 259, 261, 1, 0, 0, 0, 260, 254, 1, 0, 0, 0, 261, 264,
                1, 0, 0, 0, 262, 260, 1, 0, 0, 0, 262, 263, 1, 0, 0, 0, 263, 267,
                1, 0, 0, 0, 264, 262, 1, 0, 0, 0, 265, 267, 3, 46, 23, 0, 266, 253,
                1, 0, 0, 0, 266, 265, 1, 0, 0, 0, 267, 268, 1, 0, 0, 0, 268, 266,
                1, 0, 0, 0, 268, 269, 1, 0, 0, 0, 269, 43, 1, 0, 0, 0, 270, 272, 5,
                151, 0, 0, 271, 273, 3, 122, 61, 0, 272, 271, 1, 0, 0, 0, 272, 273,
                1, 0, 0, 0, 273, 275, 1, 0, 0, 0, 274, 276, 3, 60, 30, 0, 275, 274,
                1, 0, 0, 0, 275, 276, 1, 0, 0, 0, 276, 278, 1, 0, 0, 0, 277, 279,
                3, 48, 24, 0, 278, 277, 1, 0, 0, 0, 278, 279, 1, 0, 0, 0, 279, 282,
                1, 0, 0, 0, 280, 281, 5, 294, 0, 0, 281, 283, 3, 78, 39, 0, 282, 280,
                1, 0, 0, 0, 282, 283, 1, 0, 0, 0, 283, 284, 1, 0, 0, 0, 284, 285,
                5, 233, 0, 0, 285, 45, 1, 0, 0, 0, 286, 287, 5, 151, 0, 0, 287, 290,
                3, 36, 18, 0, 288, 289, 5, 294, 0, 0, 289, 291, 3, 78, 39, 0, 290,
                288, 1, 0, 0, 0, 290, 291, 1, 0, 0, 0, 291, 292, 1, 0, 0, 0, 292,
                294, 5, 233, 0, 0, 293, 295, 3, 38, 19, 0, 294, 293, 1, 0, 0, 0, 294,
                295, 1, 0, 0, 0, 295, 47, 1, 0, 0, 0, 296, 299, 3, 136, 68, 0, 297,
                299, 3, 118, 59, 0, 298, 296, 1, 0, 0, 0, 298, 297, 1, 0, 0, 0, 299,
                49, 1, 0, 0, 0, 300, 302, 3, 52, 26, 0, 301, 300, 1, 0, 0, 0, 301,
                302, 1, 0, 0, 0, 302, 303, 1, 0, 0, 0, 303, 322, 3, 54, 27, 0, 304,
                306, 5, 142, 0, 0, 305, 307, 3, 122, 61, 0, 306, 305, 1, 0, 0, 0,
                306, 307, 1, 0, 0, 0, 307, 309, 1, 0, 0, 0, 308, 310, 3, 60, 30, 0,
                309, 308, 1, 0, 0, 0, 309, 310, 1, 0, 0, 0, 310, 312, 1, 0, 0, 0,
                311, 313, 3, 58, 29, 0, 312, 311, 1, 0, 0, 0, 312, 313, 1, 0, 0, 0,
                313, 315, 1, 0, 0, 0, 314, 316, 3, 48, 24, 0, 315, 314, 1, 0, 0, 0,
                315, 316, 1, 0, 0, 0, 316, 319, 1, 0, 0, 0, 317, 318, 5, 294, 0, 0,
                318, 320, 3, 78, 39, 0, 319, 317, 1, 0, 0, 0, 319, 320, 1, 0, 0, 0,
                320, 321, 1, 0, 0, 0, 321, 323, 5, 210, 0, 0, 322, 304, 1, 0, 0, 0,
                322, 323, 1, 0, 0, 0, 323, 324, 1, 0, 0, 0, 324, 326, 3, 54, 27, 0,
                325, 327, 3, 56, 28, 0, 326, 325, 1, 0, 0, 0, 326, 327, 1, 0, 0, 0,
                327, 51, 1, 0, 0, 0, 328, 329, 7, 3, 0, 0, 329, 53, 1, 0, 0, 0, 330,
                331, 7, 4, 0, 0, 331, 55, 1, 0, 0, 0, 332, 333, 7, 5, 0, 0, 333, 57,
                1, 0, 0, 0, 334, 343, 5, 265, 0, 0, 335, 337, 5, 5, 0, 0, 336, 335,
                1, 0, 0, 0, 336, 337, 1, 0, 0, 0, 337, 338, 1, 0, 0, 0, 338, 340,
                5, 80, 0, 0, 339, 341, 5, 5, 0, 0, 340, 339, 1, 0, 0, 0, 340, 341,
                1, 0, 0, 0, 341, 344, 1, 0, 0, 0, 342, 344, 5, 5, 0, 0, 343, 336,
                1, 0, 0, 0, 343, 342, 1, 0, 0, 0, 343, 344, 1, 0, 0, 0, 344, 59, 1,
                0, 0, 0, 345, 346, 5, 44, 0, 0, 346, 350, 3, 62, 31, 0, 347, 348,
                5, 135, 0, 0, 348, 350, 3, 64, 32, 0, 349, 345, 1, 0, 0, 0, 349, 347,
                1, 0, 0, 0, 350, 61, 1, 0, 0, 0, 351, 359, 3, 66, 33, 0, 352, 354,
                5, 29, 0, 0, 353, 355, 5, 44, 0, 0, 354, 353, 1, 0, 0, 0, 354, 355,
                1, 0, 0, 0, 355, 356, 1, 0, 0, 0, 356, 358, 3, 66, 33, 0, 357, 352,
                1, 0, 0, 0, 358, 361, 1, 0, 0, 0, 359, 357, 1, 0, 0, 0, 359, 360,
                1, 0, 0, 0, 360, 63, 1, 0, 0, 0, 361, 359, 1, 0, 0, 0, 362, 370, 3,
                68, 34, 0, 363, 365, 5, 29, 0, 0, 364, 366, 5, 44, 0, 0, 365, 364,
                1, 0, 0, 0, 365, 366, 1, 0, 0, 0, 366, 367, 1, 0, 0, 0, 367, 369,
                3, 68, 34, 0, 368, 363, 1, 0, 0, 0, 369, 372, 1, 0, 0, 0, 370, 368,
                1, 0, 0, 0, 370, 371, 1, 0, 0, 0, 371, 65, 1, 0, 0, 0, 372, 370, 1,
                0, 0, 0, 373, 378, 3, 70, 35, 0, 374, 375, 7, 6, 0, 0, 375, 377, 3,
                70, 35, 0, 376, 374, 1, 0, 0, 0, 377, 380, 1, 0, 0, 0, 378, 376, 1,
                0, 0, 0, 378, 379, 1, 0, 0, 0, 379, 67, 1, 0, 0, 0, 380, 378, 1, 0,
                0, 0, 381, 386, 3, 72, 36, 0, 382, 383, 7, 6, 0, 0, 383, 385, 3, 72,
                36, 0, 384, 382, 1, 0, 0, 0, 385, 388, 1, 0, 0, 0, 386, 384, 1, 0,
                0, 0, 386, 387, 1, 0, 0, 0, 387, 69, 1, 0, 0, 0, 388, 386, 1, 0, 0,
                0, 389, 391, 5, 141, 0, 0, 390, 389, 1, 0, 0, 0, 391, 394, 1, 0, 0,
                0, 392, 390, 1, 0, 0, 0, 392, 393, 1, 0, 0, 0, 393, 395, 1, 0, 0,
                0, 394, 392, 1, 0, 0, 0, 395, 396, 3, 74, 37, 0, 396, 71, 1, 0, 0,
                0, 397, 399, 5, 141, 0, 0, 398, 397, 1, 0, 0, 0, 399, 402, 1, 0, 0,
                0, 400, 398, 1, 0, 0, 0, 400, 401, 1, 0, 0, 0, 401, 403, 1, 0, 0,
                0, 402, 400, 1, 0, 0, 0, 403, 404, 3, 76, 38, 0, 404, 73, 1, 0, 0,
                0, 405, 406, 5, 151, 0, 0, 406, 407, 3, 62, 31, 0, 407, 408, 5, 233,
                0, 0, 408, 412, 1, 0, 0, 0, 409, 412, 5, 158, 0, 0, 410, 412, 3, 138,
                69, 0, 411, 405, 1, 0, 0, 0, 411, 409, 1, 0, 0, 0, 411, 410, 1, 0,
                0, 0, 412, 75, 1, 0, 0, 0, 413, 414, 5, 151, 0, 0, 414, 415, 3, 64,
                32, 0, 415, 416, 5, 233, 0, 0, 416, 420, 1, 0, 0, 0, 417, 420, 5,
                158, 0, 0, 418, 420, 3, 144, 72, 0, 419, 413, 1, 0, 0, 0, 419, 417,
                1, 0, 0, 0, 419, 418, 1, 0, 0, 0, 420, 77, 1, 0, 0, 0, 421, 426, 3,
                80, 40, 0, 422, 423, 5, 186, 0, 0, 423, 425, 3, 80, 40, 0, 424, 422,
                1, 0, 0, 0, 425, 428, 1, 0, 0, 0, 426, 424, 1, 0, 0, 0, 426, 427,
                1, 0, 0, 0, 427, 79, 1, 0, 0, 0, 428, 426, 1, 0, 0, 0, 429, 434, 3,
                82, 41, 0, 430, 431, 5, 298, 0, 0, 431, 433, 3, 82, 41, 0, 432, 430,
                1, 0, 0, 0, 433, 436, 1, 0, 0, 0, 434, 432, 1, 0, 0, 0, 434, 435,
                1, 0, 0, 0, 435, 81, 1, 0, 0, 0, 436, 434, 1, 0, 0, 0, 437, 442, 3,
                84, 42, 0, 438, 439, 5, 20, 0, 0, 439, 441, 3, 84, 42, 0, 440, 438,
                1, 0, 0, 0, 441, 444, 1, 0, 0, 0, 442, 440, 1, 0, 0, 0, 442, 443,
                1, 0, 0, 0, 443, 83, 1, 0, 0, 0, 444, 442, 1, 0, 0, 0, 445, 447, 5,
                175, 0, 0, 446, 445, 1, 0, 0, 0, 447, 450, 1, 0, 0, 0, 448, 446, 1,
                0, 0, 0, 448, 449, 1, 0, 0, 0, 449, 451, 1, 0, 0, 0, 450, 448, 1,
                0, 0, 0, 451, 452, 3, 86, 43, 0, 452, 85, 1, 0, 0, 0, 453, 458, 3,
                88, 44, 0, 454, 455, 7, 7, 0, 0, 455, 457, 3, 88, 44, 0, 456, 454,
                1, 0, 0, 0, 457, 460, 1, 0, 0, 0, 458, 456, 1, 0, 0, 0, 458, 459,
                1, 0, 0, 0, 459, 87, 1, 0, 0, 0, 460, 458, 1, 0, 0, 0, 461, 463, 3,
                94, 47, 0, 462, 464, 3, 90, 45, 0, 463, 462, 1, 0, 0, 0, 463, 464,
                1, 0, 0, 0, 464, 89, 1, 0, 0, 0, 465, 473, 5, 216, 0, 0, 466, 467,
                5, 254, 0, 0, 467, 473, 5, 295, 0, 0, 468, 469, 5, 95, 0, 0, 469,
                473, 5, 295, 0, 0, 470, 473, 5, 53, 0, 0, 471, 473, 5, 127, 0, 0,
                472, 465, 1, 0, 0, 0, 472, 466, 1, 0, 0, 0, 472, 468, 1, 0, 0, 0,
                472, 470, 1, 0, 0, 0, 472, 471, 1, 0, 0, 0, 473, 474, 1, 0, 0, 0,
                474, 498, 3, 94, 47, 0, 475, 477, 5, 135, 0, 0, 476, 478, 5, 175,
                0, 0, 477, 476, 1, 0, 0, 0, 477, 478, 1, 0, 0, 0, 478, 479, 1, 0,
                0, 0, 479, 498, 5, 178, 0, 0, 480, 482, 5, 135, 0, 0, 481, 483, 5,
                175, 0, 0, 482, 481, 1, 0, 0, 0, 482, 483, 1, 0, 0, 0, 483, 484, 1,
                0, 0, 0, 484, 487, 7, 8, 0, 0, 485, 487, 5, 45, 0, 0, 486, 480, 1,
                0, 0, 0, 486, 485, 1, 0, 0, 0, 487, 488, 1, 0, 0, 0, 488, 498, 3,
                124, 62, 0, 489, 491, 5, 135, 0, 0, 490, 492, 5, 175, 0, 0, 491, 490,
                1, 0, 0, 0, 491, 492, 1, 0, 0, 0, 492, 494, 1, 0, 0, 0, 493, 495,
                3, 92, 46, 0, 494, 493, 1, 0, 0, 0, 494, 495, 1, 0, 0, 0, 495, 496,
                1, 0, 0, 0, 496, 498, 5, 174, 0, 0, 497, 472, 1, 0, 0, 0, 497, 475,
                1, 0, 0, 0, 497, 486, 1, 0, 0, 0, 497, 489, 1, 0, 0, 0, 498, 91, 1,
                0, 0, 0, 499, 500, 7, 9, 0, 0, 500, 93, 1, 0, 0, 0, 501, 506, 3, 96,
                48, 0, 502, 503, 7, 10, 0, 0, 503, 505, 3, 96, 48, 0, 504, 502, 1,
                0, 0, 0, 505, 508, 1, 0, 0, 0, 506, 504, 1, 0, 0, 0, 506, 507, 1,
                0, 0, 0, 507, 95, 1, 0, 0, 0, 508, 506, 1, 0, 0, 0, 509, 514, 3, 98,
                49, 0, 510, 511, 7, 11, 0, 0, 511, 513, 3, 98, 49, 0, 512, 510, 1,
                0, 0, 0, 513, 516, 1, 0, 0, 0, 514, 512, 1, 0, 0, 0, 514, 515, 1,
                0, 0, 0, 515, 97, 1, 0, 0, 0, 516, 514, 1, 0, 0, 0, 517, 522, 3, 100,
                50, 0, 518, 519, 5, 197, 0, 0, 519, 521, 3, 100, 50, 0, 520, 518,
                1, 0, 0, 0, 521, 524, 1, 0, 0, 0, 522, 520, 1, 0, 0, 0, 522, 523,
                1, 0, 0, 0, 523, 99, 1, 0, 0, 0, 524, 522, 1, 0, 0, 0, 525, 529, 3,
                102, 51, 0, 526, 527, 7, 12, 0, 0, 527, 529, 3, 102, 51, 0, 528, 525,
                1, 0, 0, 0, 528, 526, 1, 0, 0, 0, 529, 101, 1, 0, 0, 0, 530, 534,
                3, 108, 54, 0, 531, 533, 3, 104, 52, 0, 532, 531, 1, 0, 0, 0, 533,
                536, 1, 0, 0, 0, 534, 532, 1, 0, 0, 0, 534, 535, 1, 0, 0, 0, 535,
                103, 1, 0, 0, 0, 536, 534, 1, 0, 0, 0, 537, 553, 3, 106, 53, 0, 538,
                553, 3, 60, 30, 0, 539, 540, 5, 142, 0, 0, 540, 541, 3, 78, 39, 0,
                541, 542, 5, 210, 0, 0, 542, 553, 1, 0, 0, 0, 543, 545, 5, 142, 0,
                0, 544, 546, 3, 78, 39, 0, 545, 544, 1, 0, 0, 0, 545, 546, 1, 0, 0,
                0, 546, 547, 1, 0, 0, 0, 547, 549, 5, 80, 0, 0, 548, 550, 3, 78, 39,
                0, 549, 548, 1, 0, 0, 0, 549, 550, 1, 0, 0, 0, 550, 551, 1, 0, 0,
                0, 551, 553, 5, 210, 0, 0, 552, 537, 1, 0, 0, 0, 552, 538, 1, 0, 0,
                0, 552, 539, 1, 0, 0, 0, 552, 543, 1, 0, 0, 0, 553, 105, 1, 0, 0,
                0, 554, 555, 5, 79, 0, 0, 555, 556, 3, 116, 58, 0, 556, 107, 1, 0,
                0, 0, 557, 562, 3, 110, 55, 0, 558, 562, 3, 118, 59, 0, 559, 562,
                3, 112, 56, 0, 560, 562, 3, 122, 61, 0, 561, 557, 1, 0, 0, 0, 561,
                558, 1, 0, 0, 0, 561, 559, 1, 0, 0, 0, 561, 560, 1, 0, 0, 0, 562,
                109, 1, 0, 0, 0, 563, 573, 3, 114, 57, 0, 564, 573, 3, 134, 67, 0,
                565, 573, 3, 136, 68, 0, 566, 573, 5, 275, 0, 0, 567, 573, 5, 104,
                0, 0, 568, 573, 5, 130, 0, 0, 569, 573, 5, 131, 0, 0, 570, 573, 5,
                163, 0, 0, 571, 573, 5, 178, 0, 0, 572, 563, 1, 0, 0, 0, 572, 564,
                1, 0, 0, 0, 572, 565, 1, 0, 0, 0, 572, 566, 1, 0, 0, 0, 572, 567,
                1, 0, 0, 0, 572, 568, 1, 0, 0, 0, 572, 569, 1, 0, 0, 0, 572, 570,
                1, 0, 0, 0, 572, 571, 1, 0, 0, 0, 573, 111, 1, 0, 0, 0, 574, 575,
                5, 151, 0, 0, 575, 576, 3, 78, 39, 0, 576, 577, 5, 233, 0, 0, 577,
                113, 1, 0, 0, 0, 578, 580, 5, 157, 0, 0, 579, 578, 1, 0, 0, 0, 579,
                580, 1, 0, 0, 0, 580, 581, 1, 0, 0, 0, 581, 582, 7, 13, 0, 0, 582,
                115, 1, 0, 0, 0, 583, 584, 3, 138, 69, 0, 584, 117, 1, 0, 0, 0, 585,
                586, 5, 76, 0, 0, 586, 587, 3, 120, 60, 0, 587, 119, 1, 0, 0, 0, 588,
                593, 3, 138, 69, 0, 589, 593, 5, 5, 0, 0, 590, 593, 5, 7, 0, 0, 591,
                593, 5, 303, 0, 0, 592, 588, 1, 0, 0, 0, 592, 589, 1, 0, 0, 0, 592,
                590, 1, 0, 0, 0, 592, 591, 1, 0, 0, 0, 593, 121, 1, 0, 0, 0, 594,
                595, 3, 138, 69, 0, 595, 123, 1, 0, 0, 0, 596, 601, 3, 126, 63, 0,
                597, 598, 5, 29, 0, 0, 598, 600, 3, 126, 63, 0, 599, 597, 1, 0, 0,
                0, 600, 603, 1, 0, 0, 0, 601, 599, 1, 0, 0, 0, 601, 602, 1, 0, 0,
                0, 602, 125, 1, 0, 0, 0, 603, 601, 1, 0, 0, 0, 604, 606, 3, 128, 64,
                0, 605, 607, 3, 130, 65, 0, 606, 605, 1, 0, 0, 0, 606, 607, 1, 0,
                0, 0, 607, 611, 1, 0, 0, 0, 608, 610, 3, 132, 66, 0, 609, 608, 1,
                0, 0, 0, 610, 613, 1, 0, 0, 0, 611, 609, 1, 0, 0, 0, 611, 612, 1,
                0, 0, 0, 612, 127, 1, 0, 0, 0, 613, 611, 1, 0, 0, 0, 614, 680, 5,
                176, 0, 0, 615, 680, 5, 178, 0, 0, 616, 680, 5, 31, 0, 0, 617, 680,
                5, 32, 0, 0, 618, 680, 5, 289, 0, 0, 619, 680, 5, 257, 0, 0, 620,
                680, 5, 133, 0, 0, 621, 623, 5, 250, 0, 0, 622, 621, 1, 0, 0, 0, 622,
                623, 1, 0, 0, 0, 623, 624, 1, 0, 0, 0, 624, 680, 5, 134, 0, 0, 625,
                680, 5, 107, 0, 0, 626, 680, 5, 63, 0, 0, 627, 628, 5, 149, 0, 0,
                628, 680, 7, 14, 0, 0, 629, 630, 5, 301, 0, 0, 630, 680, 7, 14, 0,
                0, 631, 632, 5, 264, 0, 0, 632, 636, 7, 15, 0, 0, 633, 637, 5, 267,
                0, 0, 634, 635, 5, 264, 0, 0, 635, 637, 5, 300, 0, 0, 636, 633, 1,
                0, 0, 0, 636, 634, 1, 0, 0, 0, 637, 680, 1, 0, 0, 0, 638, 639, 5,
                266, 0, 0, 639, 643, 7, 15, 0, 0, 640, 644, 5, 267, 0, 0, 641, 642,
                5, 264, 0, 0, 642, 644, 5, 300, 0, 0, 643, 640, 1, 0, 0, 0, 643, 641,
                1, 0, 0, 0, 644, 680, 1, 0, 0, 0, 645, 680, 5, 86, 0, 0, 646, 680,
                5, 195, 0, 0, 647, 680, 5, 169, 0, 0, 648, 680, 5, 291, 0, 0, 649,
                680, 5, 218, 0, 0, 650, 680, 5, 88, 0, 0, 651, 680, 5, 154, 0, 0,
                652, 653, 7, 16, 0, 0, 653, 654, 5, 152, 0, 0, 654, 655, 3, 124, 62,
                0, 655, 656, 5, 120, 0, 0, 656, 680, 1, 0, 0, 0, 657, 680, 5, 190,
                0, 0, 658, 680, 5, 191, 0, 0, 659, 660, 5, 205, 0, 0, 660, 680, 5,
                288, 0, 0, 661, 677, 5, 21, 0, 0, 662, 678, 5, 169, 0, 0, 663, 678,
                5, 291, 0, 0, 664, 678, 5, 218, 0, 0, 665, 678, 5, 88, 0, 0, 666,
                678, 5, 154, 0, 0, 667, 668, 5, 205, 0, 0, 668, 678, 5, 288, 0, 0,
                669, 671, 5, 288, 0, 0, 670, 669, 1, 0, 0, 0, 670, 671, 1, 0, 0, 0,
                671, 672, 1, 0, 0, 0, 672, 673, 5, 152, 0, 0, 673, 674, 3, 124, 62,
                0, 674, 675, 5, 120, 0, 0, 675, 678, 1, 0, 0, 0, 676, 678, 5, 288,
                0, 0, 677, 662, 1, 0, 0, 0, 677, 663, 1, 0, 0, 0, 677, 664, 1, 0,
                0, 0, 677, 665, 1, 0, 0, 0, 677, 666, 1, 0, 0, 0, 677, 667, 1, 0,
                0, 0, 677, 670, 1, 0, 0, 0, 677, 676, 1, 0, 0, 0, 677, 678, 1, 0,
                0, 0, 678, 680, 1, 0, 0, 0, 679, 614, 1, 0, 0, 0, 679, 615, 1, 0,
                0, 0, 679, 616, 1, 0, 0, 0, 679, 617, 1, 0, 0, 0, 679, 618, 1, 0,
                0, 0, 679, 619, 1, 0, 0, 0, 679, 620, 1, 0, 0, 0, 679, 622, 1, 0,
                0, 0, 679, 625, 1, 0, 0, 0, 679, 626, 1, 0, 0, 0, 679, 627, 1, 0,
                0, 0, 679, 629, 1, 0, 0, 0, 679, 631, 1, 0, 0, 0, 679, 638, 1, 0,
                0, 0, 679, 645, 1, 0, 0, 0, 679, 646, 1, 0, 0, 0, 679, 647, 1, 0,
                0, 0, 679, 648, 1, 0, 0, 0, 679, 649, 1, 0, 0, 0, 679, 650, 1, 0,
                0, 0, 679, 651, 1, 0, 0, 0, 679, 652, 1, 0, 0, 0, 679, 657, 1, 0,
                0, 0, 679, 658, 1, 0, 0, 0, 679, 659, 1, 0, 0, 0, 679, 661, 1, 0,
                0, 0, 680, 129, 1, 0, 0, 0, 681, 682, 5, 175, 0, 0, 682, 685, 5, 178,
                0, 0, 683, 685, 5, 141, 0, 0, 684, 681, 1, 0, 0, 0, 684, 683, 1, 0,
                0, 0, 685, 131, 1, 0, 0, 0, 686, 688, 7, 16, 0, 0, 687, 689, 3, 130,
                65, 0, 688, 687, 1, 0, 0, 0, 688, 689, 1, 0, 0, 0, 689, 133, 1, 0,
                0, 0, 690, 691, 7, 17, 0, 0, 691, 135, 1, 0, 0, 0, 692, 706, 5, 143,
                0, 0, 693, 694, 3, 116, 58, 0, 694, 695, 5, 44, 0, 0, 695, 703, 3,
                78, 39, 0, 696, 697, 5, 46, 0, 0, 697, 698, 3, 116, 58, 0, 698, 699,
                5, 44, 0, 0, 699, 700, 3, 78, 39, 0, 700, 702, 1, 0, 0, 0, 701, 696,
                1, 0, 0, 0, 702, 705, 1, 0, 0, 0, 703, 701, 1, 0, 0, 0, 703, 704,
                1, 0, 0, 0, 704, 707, 1, 0, 0, 0, 705, 703, 1, 0, 0, 0, 706, 693,
                1, 0, 0, 0, 706, 707, 1, 0, 0, 0, 707, 708, 1, 0, 0, 0, 708, 709,
                5, 211, 0, 0, 709, 137, 1, 0, 0, 0, 710, 713, 3, 140, 70, 0, 711,
                713, 3, 142, 71, 0, 712, 710, 1, 0, 0, 0, 712, 711, 1, 0, 0, 0, 713,
                139, 1, 0, 0, 0, 714, 715, 5, 10, 0, 0, 715, 141, 1, 0, 0, 0, 716,
                726, 3, 146, 73, 0, 717, 726, 5, 175, 0, 0, 718, 726, 5, 178, 0, 0,
                719, 726, 5, 277, 0, 0, 720, 726, 5, 174, 0, 0, 721, 726, 5, 164,
                0, 0, 722, 726, 5, 165, 0, 0, 723, 726, 5, 166, 0, 0, 724, 726, 5,
                167, 0, 0, 725, 716, 1, 0, 0, 0, 725, 717, 1, 0, 0, 0, 725, 718, 1,
                0, 0, 0, 725, 719, 1, 0, 0, 0, 725, 720, 1, 0, 0, 0, 725, 721, 1,
                0, 0, 0, 725, 722, 1, 0, 0, 0, 725, 723, 1, 0, 0, 0, 725, 724, 1,
                0, 0, 0, 726, 143, 1, 0, 0, 0, 727, 730, 3, 140, 70, 0, 728, 730,
                3, 146, 73, 0, 729, 727, 1, 0, 0, 0, 729, 728, 1, 0, 0, 0, 730, 145,
                1, 0, 0, 0, 731, 732, 3, 148, 74, 0, 732, 147, 1, 0, 0, 0, 733, 734,
                7, 18, 0, 0, 734, 149, 1, 0, 0, 0, 735, 736, 5, 0, 0, 1, 736, 151,
                1, 0, 0, 0, 87, 154, 163, 172, 176, 179, 182, 191, 204, 219, 231,
                240, 244, 249, 256, 262, 266, 268, 272, 275, 278, 282, 290, 294, 298,
                301, 306, 309, 312, 315, 319, 322, 326, 336, 340, 343, 349, 354, 359,
                365, 370, 378, 386, 392, 400, 411, 419, 426, 434, 442, 448, 458, 463,
                472, 477, 482, 486, 491, 494, 497, 506, 514, 522, 528, 534, 545, 549,
                552, 561, 572, 579, 592, 601, 606, 611, 622, 636, 643, 670, 677, 679,
                684, 688, 703, 706, 712, 725, 729];
        protected static $atn;
        protected static $decisionToDFA;
        protected static $sharedContextCache;

        public function __construct(TokenStream $input)
        {
            parent::__construct($input);

            self::initialize();

            $this->interp = new ParserATNSimulator($this, self::$atn, self::$decisionToDFA, self::$sharedContextCache);
        }

        private static function initialize(): void
        {
            if (null !== self::$atn) {
                return;
            }

            RuntimeMetaData::checkVersion('4.13.2', RuntimeMetaData::VERSION);

            $atn = (new ATNDeserializer())->deserialize(self::SERIALIZED_ATN);

            $decisionToDFA = [];
            for ($i = 0, $count = $atn->getNumberOfDecisions(); $i < $count; ++$i) {
                $decisionToDFA[] = new DFA($atn->getDecisionState($i), $i);
            }

            self::$atn = $atn;
            self::$decisionToDFA = $decisionToDFA;
            self::$sharedContextCache = new PredictionContextCache();
        }

        public function getGrammarFileName(): string
        {
            return 'CypherPathSubset.g4';
        }

        public function getRuleNames(): array
        {
            return self::RULE_NAMES;
        }

        public function getSerializedATN(): array
        {
            return self::SERIALIZED_ATN;
        }

        public function getATN(): ATN
        {
            return self::$atn;
        }

        public function getVocabulary(): Vocabulary
        {
            static $vocabulary;

            return $vocabulary = $vocabulary ?? new VocabularyImpl(self::LITERAL_NAMES, self::SYMBOLIC_NAMES);
        }

        /**
         * @throws RecognitionException
         */
        public function statements(): Context\StatementsContext
        {
            $localContext = new Context\StatementsContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 0, self::RULE_statements);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(152);
                $this->statement();
                $this->setState(154);
                $this->errorHandler->sync($this);
                $_la = $this->input->LA(1);

                if (self::SEMICOLON === $_la) {
                    $this->setState(153);
                    $this->match(self::SEMICOLON);
                }
                $this->setState(156);
                $this->match(self::EOF);
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function statement(): Context\StatementContext
        {
            $localContext = new Context\StatementContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 2, self::RULE_statement);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(158);
                $this->regularQuery();
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function regularQuery(): Context\RegularQueryContext
        {
            $localContext = new Context\RegularQueryContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 4, self::RULE_regularQuery);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(160);
                $this->singleQuery();
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function singleQuery(): Context\SingleQueryContext
        {
            $localContext = new Context\SingleQueryContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 6, self::RULE_singleQuery);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(163);
                $this->errorHandler->sync($this);
                $_la = $this->input->LA(1);

                if (self::UNWIND === $_la) {
                    $this->setState(162);
                    $this->unwindClause();
                }
                $this->setState(165);
                $this->matchClause();
                $this->setState(166);
                $this->returnClause();
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function returnClause(): Context\ReturnClauseContext
        {
            $localContext = new Context\ReturnClauseContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 8, self::RULE_returnClause);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(168);
                $this->match(self::RETURN);
                $this->setState(169);
                $this->returnBody();
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function returnBody(): Context\ReturnBodyContext
        {
            $localContext = new Context\ReturnBodyContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 10, self::RULE_returnBody);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(172);
                $this->errorHandler->sync($this);

                switch ($this->getInterpreter()->adaptivePredict($this->input, 2, $this->ctx)) {
                    case 1:
                        $this->setState(171);
                        $this->match(self::DISTINCT);
                        break;
                }
                $this->setState(174);
                $this->returnItems();
                $this->setState(176);
                $this->errorHandler->sync($this);
                $_la = $this->input->LA(1);

                if (self::ORDER === $_la) {
                    $this->setState(175);
                    $this->orderBy();
                }
                $this->setState(179);
                $this->errorHandler->sync($this);
                $_la = $this->input->LA(1);

                if (self::OFFSET === $_la || self::SKIPROWS === $_la) {
                    $this->setState(178);
                    $this->skip();
                }
                $this->setState(182);
                $this->errorHandler->sync($this);
                $_la = $this->input->LA(1);

                if (self::LIMITROWS === $_la) {
                    $this->setState(181);
                    $this->limit();
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function returnItem(): Context\ReturnItemContext
        {
            $localContext = new Context\ReturnItemContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 12, self::RULE_returnItem);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(184);
                $this->variable();
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function returnItems(): Context\ReturnItemsContext
        {
            $localContext = new Context\ReturnItemsContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 14, self::RULE_returnItems);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(186);
                $this->returnItem();
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function orderItem(): Context\OrderItemContext
        {
            $localContext = new Context\OrderItemContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 16, self::RULE_orderItem);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(188);
                $this->expression();
                $this->setState(191);
                $this->errorHandler->sync($this);

                switch ($this->input->LA(1)) {
                    case self::ASC:
                    case self::ASCENDING:
                        $this->setState(189);
                        $this->ascToken();
                        break;

                    case self::DESC:
                    case self::DESCENDING:
                        $this->setState(190);
                        $this->descToken();
                        break;

                    case self::EOF:
                    case self::COMMA:
                    case self::LIMITROWS:
                    case self::OFFSET:
                    case self::SEMICOLON:
                    case self::SKIPROWS:
                        break;

                    default:
                        break;
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function ascToken(): Context\AscTokenContext
        {
            $localContext = new Context\AscTokenContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 18, self::RULE_ascToken);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(193);

                $_la = $this->input->LA(1);

                if (!(self::ASC === $_la || self::ASCENDING === $_la)) {
                    $this->errorHandler->recoverInline($this);
                } else {
                    if (Token::EOF === $this->input->LA(1)) {
                        $this->matchedEOF = true;
                    }

                    $this->errorHandler->reportMatch($this);
                    $this->consume();
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function descToken(): Context\DescTokenContext
        {
            $localContext = new Context\DescTokenContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 20, self::RULE_descToken);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(195);

                $_la = $this->input->LA(1);

                if (!(self::DESC === $_la || self::DESCENDING === $_la)) {
                    $this->errorHandler->recoverInline($this);
                } else {
                    if (Token::EOF === $this->input->LA(1)) {
                        $this->matchedEOF = true;
                    }

                    $this->errorHandler->reportMatch($this);
                    $this->consume();
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function orderBy(): Context\OrderByContext
        {
            $localContext = new Context\OrderByContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 22, self::RULE_orderBy);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(197);
                $this->match(self::ORDER);
                $this->setState(198);
                $this->match(self::BY);
                $this->setState(199);
                $this->orderItem();
                $this->setState(204);
                $this->errorHandler->sync($this);

                $_la = $this->input->LA(1);
                while (self::COMMA === $_la) {
                    $this->setState(200);
                    $this->match(self::COMMA);
                    $this->setState(201);
                    $this->orderItem();
                    $this->setState(206);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function skip(): Context\SkipContext
        {
            $localContext = new Context\SkipContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 24, self::RULE_skip);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(207);

                $_la = $this->input->LA(1);

                if (!(self::OFFSET === $_la || self::SKIPROWS === $_la)) {
                    $this->errorHandler->recoverInline($this);
                } else {
                    if (Token::EOF === $this->input->LA(1)) {
                        $this->matchedEOF = true;
                    }

                    $this->errorHandler->reportMatch($this);
                    $this->consume();
                }
                $this->setState(208);
                $this->expression();
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function limit(): Context\LimitContext
        {
            $localContext = new Context\LimitContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 26, self::RULE_limit);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(210);
                $this->match(self::LIMITROWS);
                $this->setState(211);
                $this->expression();
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function whereClause(): Context\WhereClauseContext
        {
            $localContext = new Context\WhereClauseContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 28, self::RULE_whereClause);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(213);
                $this->match(self::WHERE);
                $this->setState(214);
                $this->expression();
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function matchClause(): Context\MatchClauseContext
        {
            $localContext = new Context\MatchClauseContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 30, self::RULE_matchClause);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(216);
                $this->match(self::MATCH);
                $this->setState(217);
                $this->patternList();
                $this->setState(219);
                $this->errorHandler->sync($this);
                $_la = $this->input->LA(1);

                if (self::WHERE === $_la) {
                    $this->setState(218);
                    $this->whereClause();
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function unwindClause(): Context\UnwindClauseContext
        {
            $localContext = new Context\UnwindClauseContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 32, self::RULE_unwindClause);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(221);
                $this->match(self::UNWIND);
                $this->setState(222);
                $this->expression();
                $this->setState(223);
                $this->match(self::AS);
                $this->setState(224);
                $this->variable();
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function patternList(): Context\PatternListContext
        {
            $localContext = new Context\PatternListContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 34, self::RULE_patternList);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(226);
                $this->pattern();
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function pattern(): Context\PatternContext
        {
            $localContext = new Context\PatternContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 36, self::RULE_pattern);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(231);
                $this->errorHandler->sync($this);
                $_la = $this->input->LA(1);

                if ((($_la & ~0x3F) === 0 && ((1 << $_la) & -123145839182848) !== 0) || ((($_la - 64) & ~0x3F) === 0 && ((1 << ($_la - 64)) & -73183498239987713) !== 0) || ((($_la - 128) & ~0x3F) === 0 && ((1 << ($_la - 128)) & -8078356481) !== 0) || ((($_la - 192) & ~0x3F) === 0 && ((1 << ($_la - 192)) & -565148994306087) !== 0) || ((($_la - 256) & ~0x3F) === 0 && ((1 << ($_la - 256)) & 140737488354815) !== 0)) {
                    $this->setState(228);
                    $this->variable();
                    $this->setState(229);
                    $this->match(self::EQ);
                }
                $this->setState(233);
                $this->anonymousPattern();
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function quantifier(): Context\QuantifierContext
        {
            $localContext = new Context\QuantifierContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 38, self::RULE_quantifier);

            try {
                $this->setState(249);
                $this->errorHandler->sync($this);

                switch ($this->getInterpreter()->adaptivePredict($this->input, 12, $this->ctx)) {
                    case 1:
                        $this->enterOuterAlt($localContext, 1);
                        $this->setState(235);
                        $this->match(self::LCURLY);
                        $this->setState(236);
                        $this->match(self::UNSIGNED_DECIMAL_INTEGER);
                        $this->setState(237);
                        $this->match(self::RCURLY);
                        break;

                    case 2:
                        $this->enterOuterAlt($localContext, 2);
                        $this->setState(238);
                        $this->match(self::LCURLY);
                        $this->setState(240);
                        $this->errorHandler->sync($this);
                        $_la = $this->input->LA(1);

                        if (self::UNSIGNED_DECIMAL_INTEGER === $_la) {
                            $this->setState(239);
                            $localContext->from = $this->match(self::UNSIGNED_DECIMAL_INTEGER);
                        }
                        $this->setState(242);
                        $this->match(self::COMMA);
                        $this->setState(244);
                        $this->errorHandler->sync($this);
                        $_la = $this->input->LA(1);

                        if (self::UNSIGNED_DECIMAL_INTEGER === $_la) {
                            $this->setState(243);
                            $localContext->to = $this->match(self::UNSIGNED_DECIMAL_INTEGER);
                        }
                        $this->setState(246);
                        $this->match(self::RCURLY);
                        break;

                    case 3:
                        $this->enterOuterAlt($localContext, 3);
                        $this->setState(247);
                        $this->match(self::PLUS);
                        break;

                    case 4:
                        $this->enterOuterAlt($localContext, 4);
                        $this->setState(248);
                        $this->match(self::TIMES);
                        break;
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function anonymousPattern(): Context\AnonymousPatternContext
        {
            $localContext = new Context\AnonymousPatternContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 40, self::RULE_anonymousPattern);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(251);
                $this->patternElement();
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function patternElement(): Context\PatternElementContext
        {
            $localContext = new Context\PatternElementContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 42, self::RULE_patternElement);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(266);
                $this->errorHandler->sync($this);

                $_la = $this->input->LA(1);
                do {
                    $this->setState(266);
                    $this->errorHandler->sync($this);

                    switch ($this->getInterpreter()->adaptivePredict($this->input, 15, $this->ctx)) {
                        case 1:
                            $this->setState(253);
                            $this->nodePattern();
                            $this->setState(262);
                            $this->errorHandler->sync($this);

                            $_la = $this->input->LA(1);
                            while (self::LT === $_la || self::MINUS === $_la || self::ARROW_LINE === $_la || self::ARROW_LEFT_HEAD === $_la) {
                                $this->setState(254);
                                $this->relationshipPattern();
                                $this->setState(256);
                                $this->errorHandler->sync($this);
                                $_la = $this->input->LA(1);

                                if (self::LCURLY === $_la || self::PLUS === $_la || self::TIMES === $_la) {
                                    $this->setState(255);
                                    $this->quantifier();
                                }
                                $this->setState(258);
                                $this->nodePattern();
                                $this->setState(264);
                                $this->errorHandler->sync($this);
                                $_la = $this->input->LA(1);
                            }
                            break;

                        case 2:
                            $this->setState(265);
                            $this->parenthesizedPath();
                            break;
                    }
                    $this->setState(268);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);
                } while (self::LPAREN === $_la);
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function nodePattern(): Context\NodePatternContext
        {
            $localContext = new Context\NodePatternContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 44, self::RULE_nodePattern);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(270);
                $this->match(self::LPAREN);
                $this->setState(272);
                $this->errorHandler->sync($this);

                switch ($this->getInterpreter()->adaptivePredict($this->input, 17, $this->ctx)) {
                    case 1:
                        $this->setState(271);
                        $this->variable();
                        break;
                }
                $this->setState(275);
                $this->errorHandler->sync($this);
                $_la = $this->input->LA(1);

                if (self::COLON === $_la || self::IS === $_la) {
                    $this->setState(274);
                    $this->labelExpression();
                }
                $this->setState(278);
                $this->errorHandler->sync($this);
                $_la = $this->input->LA(1);

                if (self::DOLLAR === $_la || self::LCURLY === $_la) {
                    $this->setState(277);
                    $this->properties();
                }
                $this->setState(282);
                $this->errorHandler->sync($this);
                $_la = $this->input->LA(1);

                if (self::WHERE === $_la) {
                    $this->setState(280);
                    $this->match(self::WHERE);
                    $this->setState(281);
                    $this->expression();
                }
                $this->setState(284);
                $this->match(self::RPAREN);
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function parenthesizedPath(): Context\ParenthesizedPathContext
        {
            $localContext = new Context\ParenthesizedPathContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 46, self::RULE_parenthesizedPath);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(286);
                $this->match(self::LPAREN);
                $this->setState(287);
                $this->pattern();
                $this->setState(290);
                $this->errorHandler->sync($this);
                $_la = $this->input->LA(1);

                if (self::WHERE === $_la) {
                    $this->setState(288);
                    $this->match(self::WHERE);
                    $this->setState(289);
                    $this->expression();
                }
                $this->setState(292);
                $this->match(self::RPAREN);
                $this->setState(294);
                $this->errorHandler->sync($this);
                $_la = $this->input->LA(1);

                if (self::LCURLY === $_la || self::PLUS === $_la || self::TIMES === $_la) {
                    $this->setState(293);
                    $this->quantifier();
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function properties(): Context\PropertiesContext
        {
            $localContext = new Context\PropertiesContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 48, self::RULE_properties);

            try {
                $this->setState(298);
                $this->errorHandler->sync($this);

                switch ($this->input->LA(1)) {
                    case self::LCURLY:
                        $this->enterOuterAlt($localContext, 1);
                        $this->setState(296);
                        $this->map();
                        break;

                    case self::DOLLAR:
                        $this->enterOuterAlt($localContext, 2);
                        $this->setState(297);
                        $this->parameter('ANY');
                        break;

                    default:
                        throw new NoViableAltException($this);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function relationshipPattern(): Context\RelationshipPatternContext
        {
            $localContext = new Context\RelationshipPatternContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 50, self::RULE_relationshipPattern);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(301);
                $this->errorHandler->sync($this);
                $_la = $this->input->LA(1);

                if (self::LT === $_la || self::ARROW_LEFT_HEAD === $_la) {
                    $this->setState(300);
                    $this->leftArrow();
                }
                $this->setState(303);
                $this->arrowLine();
                $this->setState(322);
                $this->errorHandler->sync($this);
                $_la = $this->input->LA(1);

                if (self::LBRACKET === $_la) {
                    $this->setState(304);
                    $this->match(self::LBRACKET);
                    $this->setState(306);
                    $this->errorHandler->sync($this);

                    switch ($this->getInterpreter()->adaptivePredict($this->input, 25, $this->ctx)) {
                        case 1:
                            $this->setState(305);
                            $this->variable();
                            break;
                    }
                    $this->setState(309);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);

                    if (self::COLON === $_la || self::IS === $_la) {
                        $this->setState(308);
                        $this->labelExpression();
                    }
                    $this->setState(312);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);

                    if (self::TIMES === $_la) {
                        $this->setState(311);
                        $this->pathLength();
                    }
                    $this->setState(315);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);

                    if (self::DOLLAR === $_la || self::LCURLY === $_la) {
                        $this->setState(314);
                        $this->properties();
                    }
                    $this->setState(319);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);

                    if (self::WHERE === $_la) {
                        $this->setState(317);
                        $this->match(self::WHERE);
                        $this->setState(318);
                        $this->expression();
                    }
                    $this->setState(321);
                    $this->match(self::RBRACKET);
                }
                $this->setState(324);
                $this->arrowLine();
                $this->setState(326);
                $this->errorHandler->sync($this);
                $_la = $this->input->LA(1);

                if (self::GT === $_la || self::ARROW_RIGHT_HEAD === $_la) {
                    $this->setState(325);
                    $this->rightArrow();
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function leftArrow(): Context\LeftArrowContext
        {
            $localContext = new Context\LeftArrowContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 52, self::RULE_leftArrow);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(328);

                $_la = $this->input->LA(1);

                if (!(self::LT === $_la || self::ARROW_LEFT_HEAD === $_la)) {
                    $this->errorHandler->recoverInline($this);
                } else {
                    if (Token::EOF === $this->input->LA(1)) {
                        $this->matchedEOF = true;
                    }

                    $this->errorHandler->reportMatch($this);
                    $this->consume();
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function arrowLine(): Context\ArrowLineContext
        {
            $localContext = new Context\ArrowLineContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 54, self::RULE_arrowLine);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(330);

                $_la = $this->input->LA(1);

                if (!(self::MINUS === $_la || self::ARROW_LINE === $_la)) {
                    $this->errorHandler->recoverInline($this);
                } else {
                    if (Token::EOF === $this->input->LA(1)) {
                        $this->matchedEOF = true;
                    }

                    $this->errorHandler->reportMatch($this);
                    $this->consume();
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function rightArrow(): Context\RightArrowContext
        {
            $localContext = new Context\RightArrowContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 56, self::RULE_rightArrow);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(332);

                $_la = $this->input->LA(1);

                if (!(self::GT === $_la || self::ARROW_RIGHT_HEAD === $_la)) {
                    $this->errorHandler->recoverInline($this);
                } else {
                    if (Token::EOF === $this->input->LA(1)) {
                        $this->matchedEOF = true;
                    }

                    $this->errorHandler->reportMatch($this);
                    $this->consume();
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function pathLength(): Context\PathLengthContext
        {
            $localContext = new Context\PathLengthContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 58, self::RULE_pathLength);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(334);
                $this->match(self::TIMES);
                $this->setState(343);
                $this->errorHandler->sync($this);

                switch ($this->getInterpreter()->adaptivePredict($this->input, 34, $this->ctx)) {
                    case 1:
                        $this->setState(336);
                        $this->errorHandler->sync($this);
                        $_la = $this->input->LA(1);

                        if (self::UNSIGNED_DECIMAL_INTEGER === $_la) {
                            $this->setState(335);
                            $localContext->from = $this->match(self::UNSIGNED_DECIMAL_INTEGER);
                        }
                        $this->setState(338);
                        $this->match(self::DOTDOT);
                        $this->setState(340);
                        $this->errorHandler->sync($this);
                        $_la = $this->input->LA(1);

                        if (self::UNSIGNED_DECIMAL_INTEGER === $_la) {
                            $this->setState(339);
                            $localContext->to = $this->match(self::UNSIGNED_DECIMAL_INTEGER);
                        }
                        break;

                    case 2:
                        $this->setState(342);
                        $localContext->single = $this->match(self::UNSIGNED_DECIMAL_INTEGER);
                        break;
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function labelExpression(): Context\LabelExpressionContext
        {
            $localContext = new Context\LabelExpressionContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 60, self::RULE_labelExpression);

            try {
                $this->setState(349);
                $this->errorHandler->sync($this);

                switch ($this->input->LA(1)) {
                    case self::COLON:
                        $this->enterOuterAlt($localContext, 1);
                        $this->setState(345);
                        $this->match(self::COLON);
                        $this->setState(346);
                        $this->labelExpression4();
                        break;

                    case self::IS:
                        $this->enterOuterAlt($localContext, 2);
                        $this->setState(347);
                        $this->match(self::IS);
                        $this->setState(348);
                        $this->labelExpression4Is();
                        break;

                    default:
                        throw new NoViableAltException($this);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function labelExpression4(): Context\LabelExpression4Context
        {
            $localContext = new Context\LabelExpression4Context($this->ctx, $this->getState());

            $this->enterRule($localContext, 62, self::RULE_labelExpression4);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(351);
                $this->labelExpression3();
                $this->setState(359);
                $this->errorHandler->sync($this);

                $_la = $this->input->LA(1);
                while (self::BAR === $_la) {
                    $this->setState(352);
                    $this->match(self::BAR);
                    $this->setState(354);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);

                    if (self::COLON === $_la) {
                        $this->setState(353);
                        $this->match(self::COLON);
                    }
                    $this->setState(356);
                    $this->labelExpression3();
                    $this->setState(361);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function labelExpression4Is(): Context\LabelExpression4IsContext
        {
            $localContext = new Context\LabelExpression4IsContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 64, self::RULE_labelExpression4Is);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(362);
                $this->labelExpression3Is();
                $this->setState(370);
                $this->errorHandler->sync($this);

                $_la = $this->input->LA(1);
                while (self::BAR === $_la) {
                    $this->setState(363);
                    $this->match(self::BAR);
                    $this->setState(365);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);

                    if (self::COLON === $_la) {
                        $this->setState(364);
                        $this->match(self::COLON);
                    }
                    $this->setState(367);
                    $this->labelExpression3Is();
                    $this->setState(372);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function labelExpression3(): Context\LabelExpression3Context
        {
            $localContext = new Context\LabelExpression3Context($this->ctx, $this->getState());

            $this->enterRule($localContext, 66, self::RULE_labelExpression3);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(373);
                $this->labelExpression2();
                $this->setState(378);
                $this->errorHandler->sync($this);

                $alt = $this->getInterpreter()->adaptivePredict($this->input, 40, $this->ctx);

                while (2 !== $alt && ATN::INVALID_ALT_NUMBER !== $alt) {
                    if (1 === $alt) {
                        $this->setState(374);

                        $_la = $this->input->LA(1);

                        if (!(self::COLON === $_la || self::AMPERSAND === $_la)) {
                            $this->errorHandler->recoverInline($this);
                        } else {
                            if (Token::EOF === $this->input->LA(1)) {
                                $this->matchedEOF = true;
                            }

                            $this->errorHandler->reportMatch($this);
                            $this->consume();
                        }
                        $this->setState(375);
                        $this->labelExpression2();
                    }

                    $this->setState(380);
                    $this->errorHandler->sync($this);

                    $alt = $this->getInterpreter()->adaptivePredict($this->input, 40, $this->ctx);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function labelExpression3Is(): Context\LabelExpression3IsContext
        {
            $localContext = new Context\LabelExpression3IsContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 68, self::RULE_labelExpression3Is);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(381);
                $this->labelExpression2Is();
                $this->setState(386);
                $this->errorHandler->sync($this);

                $alt = $this->getInterpreter()->adaptivePredict($this->input, 41, $this->ctx);

                while (2 !== $alt && ATN::INVALID_ALT_NUMBER !== $alt) {
                    if (1 === $alt) {
                        $this->setState(382);

                        $_la = $this->input->LA(1);

                        if (!(self::COLON === $_la || self::AMPERSAND === $_la)) {
                            $this->errorHandler->recoverInline($this);
                        } else {
                            if (Token::EOF === $this->input->LA(1)) {
                                $this->matchedEOF = true;
                            }

                            $this->errorHandler->reportMatch($this);
                            $this->consume();
                        }
                        $this->setState(383);
                        $this->labelExpression2Is();
                    }

                    $this->setState(388);
                    $this->errorHandler->sync($this);

                    $alt = $this->getInterpreter()->adaptivePredict($this->input, 41, $this->ctx);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function labelExpression2(): Context\LabelExpression2Context
        {
            $localContext = new Context\LabelExpression2Context($this->ctx, $this->getState());

            $this->enterRule($localContext, 70, self::RULE_labelExpression2);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(392);
                $this->errorHandler->sync($this);

                $_la = $this->input->LA(1);
                while (self::EXCLAMATION_MARK === $_la) {
                    $this->setState(389);
                    $this->match(self::EXCLAMATION_MARK);
                    $this->setState(394);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);
                }
                $this->setState(395);
                $this->labelExpression1();
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function labelExpression2Is(): Context\LabelExpression2IsContext
        {
            $localContext = new Context\LabelExpression2IsContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 72, self::RULE_labelExpression2Is);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(400);
                $this->errorHandler->sync($this);

                $_la = $this->input->LA(1);
                while (self::EXCLAMATION_MARK === $_la) {
                    $this->setState(397);
                    $this->match(self::EXCLAMATION_MARK);
                    $this->setState(402);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);
                }
                $this->setState(403);
                $this->labelExpression1Is();
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function labelExpression1(): Context\LabelExpression1Context
        {
            $localContext = new Context\LabelExpression1Context($this->ctx, $this->getState());

            $this->enterRule($localContext, 74, self::RULE_labelExpression1);

            try {
                $this->setState(411);
                $this->errorHandler->sync($this);

                switch ($this->input->LA(1)) {
                    case self::LPAREN:
                        $localContext = new Context\ParenthesizedLabelExpressionContext($localContext);
                        $this->enterOuterAlt($localContext, 1);
                        $this->setState(405);
                        $this->match(self::LPAREN);
                        $this->setState(406);
                        $this->labelExpression4();
                        $this->setState(407);
                        $this->match(self::RPAREN);
                        break;

                    case self::PERCENT:
                        $localContext = new Context\AnyLabelContext($localContext);
                        $this->enterOuterAlt($localContext, 2);
                        $this->setState(409);
                        $this->match(self::PERCENT);
                        break;

                    case self::ESCAPED_SYMBOLIC_NAME:
                    case self::ACCESS:
                    case self::ACTIVE:
                    case self::ADMIN:
                    case self::ADMINISTRATOR:
                    case self::ALIAS:
                    case self::ALIASES:
                    case self::ALL_SHORTEST_PATHS:
                    case self::ALL:
                    case self::ALTER:
                    case self::AND:
                    case self::ANY:
                    case self::ARRAY:
                    case self::AS:
                    case self::ASC:
                    case self::ASCENDING:
                    case self::ASSIGN:
                    case self::AT:
                    case self::AUTH:
                    case self::BINDINGS:
                    case self::BOOL:
                    case self::BOOLEAN:
                    case self::BOOSTED:
                    case self::BOTH:
                    case self::BREAK:
                    case self::BUILT:
                    case self::BY:
                    case self::CALL:
                    case self::CASCADE:
                    case self::CASE:
                    case self::CHANGE:
                    case self::CIDR:
                    case self::COLLECT:
                    case self::COMMAND:
                    case self::COMMANDS:
                    case self::COMPOSITE:
                    case self::CONCURRENT:
                    case self::CONSTRAINT:
                    case self::CONSTRAINTS:
                    case self::CONTAINS:
                    case self::COPY:
                    case self::CONTINUE:
                    case self::COUNT:
                    case self::CREATE:
                    case self::CSV:
                    case self::CURRENT:
                    case self::DATA:
                    case self::DATABASE:
                    case self::DATABASES:
                    case self::DATE:
                    case self::DATETIME:
                    case self::DBMS:
                    case self::DEALLOCATE:
                    case self::DEFAULT:
                    case self::DEFINED:
                    case self::DELETE:
                    case self::DENY:
                    case self::DESC:
                    case self::DESCENDING:
                    case self::DESTROY:
                    case self::DETACH:
                    case self::DIFFERENT:
                    case self::DISTINCT:
                    case self::DRIVER:
                    case self::DROP:
                    case self::DRYRUN:
                    case self::DUMP:
                    case self::DURATION:
                    case self::EACH:
                    case self::EDGE:
                    case self::ENABLE:
                    case self::ELEMENT:
                    case self::ELEMENTS:
                    case self::ELSE:
                    case self::ENCRYPTED:
                    case self::END:
                    case self::ENDS:
                    case self::EXECUTABLE:
                    case self::EXECUTE:
                    case self::EXIST:
                    case self::EXISTENCE:
                    case self::EXISTS:
                    case self::ERROR:
                    case self::FAIL:
                    case self::FALSE:
                    case self::FIELDTERMINATOR:
                    case self::FINISH:
                    case self::FLOAT:
                    case self::FOR:
                    case self::FOREACH:
                    case self::FROM:
                    case self::FULLTEXT:
                    case self::FUNCTION:
                    case self::FUNCTIONS:
                    case self::GRANT:
                    case self::GRAPH:
                    case self::GRAPHS:
                    case self::GROUP:
                    case self::GROUPS:
                    case self::HEADERS:
                    case self::HOME:
                    case self::ID:
                    case self::IF:
                    case self::IMPERSONATE:
                    case self::IMMUTABLE:
                    case self::IN:
                    case self::INDEX:
                    case self::INDEXES:
                    case self::INF:
                    case self::INFINITY:
                    case self::INSERT:
                    case self::INT:
                    case self::INTEGER:
                    case self::IS:
                    case self::JOIN:
                    case self::KEY:
                    case self::LABEL:
                    case self::LABELS:
                    case self::LEADING:
                    case self::LIMITROWS:
                    case self::LIST:
                    case self::LOAD:
                    case self::LOCAL:
                    case self::LOOKUP:
                    case self::MANAGEMENT:
                    case self::MAP:
                    case self::MATCH:
                    case self::MERGE:
                    case self::NAME:
                    case self::NAMES:
                    case self::NAN:
                    case self::NFC:
                    case self::NFD:
                    case self::NFKC:
                    case self::NFKD:
                    case self::NEW:
                    case self::NODE:
                    case self::NODETACH:
                    case self::NODES:
                    case self::NONE:
                    case self::NORMALIZE:
                    case self::NORMALIZED:
                    case self::NOT:
                    case self::NOTHING:
                    case self::NOWAIT:
                    case self::NULL:
                    case self::OF:
                    case self::OFFSET:
                    case self::ON:
                    case self::ONLY:
                    case self::OPTIONAL:
                    case self::OPTIONS:
                    case self::OPTION:
                    case self::OR:
                    case self::ORDER:
                    case self::PASSWORD:
                    case self::PASSWORDS:
                    case self::PATH:
                    case self::PATHS:
                    case self::PLAINTEXT:
                    case self::POINT:
                    case self::POPULATED:
                    case self::PRIMARY:
                    case self::PRIMARIES:
                    case self::PRIVILEGE:
                    case self::PRIVILEGES:
                    case self::PROCEDURE:
                    case self::PROCEDURES:
                    case self::PROPERTIES:
                    case self::PROPERTY:
                    case self::PROVIDER:
                    case self::PROVIDERS:
                    case self::RANGE:
                    case self::READ:
                    case self::REALLOCATE:
                    case self::REDUCE:
                    case self::RENAME:
                    case self::REL:
                    case self::RELATIONSHIP:
                    case self::RELATIONSHIPS:
                    case self::REMOVE:
                    case self::REPEATABLE:
                    case self::REPLACE:
                    case self::REPORT:
                    case self::REQUIRE:
                    case self::REQUIRED:
                    case self::RESTRICT:
                    case self::RETURN:
                    case self::REVOKE:
                    case self::ROLE:
                    case self::ROLES:
                    case self::ROW:
                    case self::ROWS:
                    case self::SCAN:
                    case self::SEC:
                    case self::SECOND:
                    case self::SECONDARY:
                    case self::SECONDARIES:
                    case self::SECONDS:
                    case self::SEEK:
                    case self::SERVER:
                    case self::SERVERS:
                    case self::SET:
                    case self::SETTING:
                    case self::SETTINGS:
                    case self::SHORTEST_PATH:
                    case self::SHORTEST:
                    case self::SHOW:
                    case self::SIGNED:
                    case self::SINGLE:
                    case self::SKIPROWS:
                    case self::START:
                    case self::STARTS:
                    case self::STATUS:
                    case self::STOP:
                    case self::STRING:
                    case self::SUPPORTED:
                    case self::SUSPENDED:
                    case self::TARGET:
                    case self::TERMINATE:
                    case self::TEXT:
                    case self::THEN:
                    case self::TIME:
                    case self::TIMESTAMP:
                    case self::TIMEZONE:
                    case self::TO:
                    case self::TOPOLOGY:
                    case self::TRAILING:
                    case self::TRANSACTION:
                    case self::TRANSACTIONS:
                    case self::TRAVERSE:
                    case self::TRIM:
                    case self::TRUE:
                    case self::TYPE:
                    case self::TYPED:
                    case self::TYPES:
                    case self::UNION:
                    case self::UNIQUE:
                    case self::UNIQUENESS:
                    case self::UNWIND:
                    case self::URL:
                    case self::USE:
                    case self::USER:
                    case self::USERS:
                    case self::USING:
                    case self::VALUE:
                    case self::VARCHAR:
                    case self::VECTOR:
                    case self::VERTEX:
                    case self::WAIT:
                    case self::WHEN:
                    case self::WHERE:
                    case self::WITH:
                    case self::WITHOUT:
                    case self::WRITE:
                    case self::XOR:
                    case self::YIELD:
                    case self::ZONE:
                    case self::ZONED:
                    case self::IDENTIFIER:
                        $localContext = new Context\LabelNameContext($localContext);
                        $this->enterOuterAlt($localContext, 3);
                        $this->setState(410);
                        $this->symbolicNameString();
                        break;

                    default:
                        throw new NoViableAltException($this);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function labelExpression1Is(): Context\LabelExpression1IsContext
        {
            $localContext = new Context\LabelExpression1IsContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 76, self::RULE_labelExpression1Is);

            try {
                $this->setState(419);
                $this->errorHandler->sync($this);

                switch ($this->input->LA(1)) {
                    case self::LPAREN:
                        $localContext = new Context\ParenthesizedLabelExpressionIsContext($localContext);
                        $this->enterOuterAlt($localContext, 1);
                        $this->setState(413);
                        $this->match(self::LPAREN);
                        $this->setState(414);
                        $this->labelExpression4Is();
                        $this->setState(415);
                        $this->match(self::RPAREN);
                        break;

                    case self::PERCENT:
                        $localContext = new Context\AnyLabelIsContext($localContext);
                        $this->enterOuterAlt($localContext, 2);
                        $this->setState(417);
                        $this->match(self::PERCENT);
                        break;

                    case self::ESCAPED_SYMBOLIC_NAME:
                    case self::ACCESS:
                    case self::ACTIVE:
                    case self::ADMIN:
                    case self::ADMINISTRATOR:
                    case self::ALIAS:
                    case self::ALIASES:
                    case self::ALL_SHORTEST_PATHS:
                    case self::ALL:
                    case self::ALTER:
                    case self::AND:
                    case self::ANY:
                    case self::ARRAY:
                    case self::AS:
                    case self::ASC:
                    case self::ASCENDING:
                    case self::ASSIGN:
                    case self::AT:
                    case self::AUTH:
                    case self::BINDINGS:
                    case self::BOOL:
                    case self::BOOLEAN:
                    case self::BOOSTED:
                    case self::BOTH:
                    case self::BREAK:
                    case self::BUILT:
                    case self::BY:
                    case self::CALL:
                    case self::CASCADE:
                    case self::CASE:
                    case self::CHANGE:
                    case self::CIDR:
                    case self::COLLECT:
                    case self::COMMAND:
                    case self::COMMANDS:
                    case self::COMPOSITE:
                    case self::CONCURRENT:
                    case self::CONSTRAINT:
                    case self::CONSTRAINTS:
                    case self::CONTAINS:
                    case self::COPY:
                    case self::CONTINUE:
                    case self::COUNT:
                    case self::CREATE:
                    case self::CSV:
                    case self::CURRENT:
                    case self::DATA:
                    case self::DATABASE:
                    case self::DATABASES:
                    case self::DATE:
                    case self::DATETIME:
                    case self::DBMS:
                    case self::DEALLOCATE:
                    case self::DEFAULT:
                    case self::DEFINED:
                    case self::DELETE:
                    case self::DENY:
                    case self::DESC:
                    case self::DESCENDING:
                    case self::DESTROY:
                    case self::DETACH:
                    case self::DIFFERENT:
                    case self::DISTINCT:
                    case self::DRIVER:
                    case self::DROP:
                    case self::DRYRUN:
                    case self::DUMP:
                    case self::DURATION:
                    case self::EACH:
                    case self::EDGE:
                    case self::ENABLE:
                    case self::ELEMENT:
                    case self::ELEMENTS:
                    case self::ELSE:
                    case self::ENCRYPTED:
                    case self::END:
                    case self::ENDS:
                    case self::EXECUTABLE:
                    case self::EXECUTE:
                    case self::EXIST:
                    case self::EXISTENCE:
                    case self::EXISTS:
                    case self::ERROR:
                    case self::FAIL:
                    case self::FALSE:
                    case self::FIELDTERMINATOR:
                    case self::FINISH:
                    case self::FLOAT:
                    case self::FOR:
                    case self::FOREACH:
                    case self::FROM:
                    case self::FULLTEXT:
                    case self::FUNCTION:
                    case self::FUNCTIONS:
                    case self::GRANT:
                    case self::GRAPH:
                    case self::GRAPHS:
                    case self::GROUP:
                    case self::GROUPS:
                    case self::HEADERS:
                    case self::HOME:
                    case self::ID:
                    case self::IF:
                    case self::IMPERSONATE:
                    case self::IMMUTABLE:
                    case self::IN:
                    case self::INDEX:
                    case self::INDEXES:
                    case self::INF:
                    case self::INFINITY:
                    case self::INSERT:
                    case self::INT:
                    case self::INTEGER:
                    case self::IS:
                    case self::JOIN:
                    case self::KEY:
                    case self::LABEL:
                    case self::LABELS:
                    case self::LEADING:
                    case self::LIMITROWS:
                    case self::LIST:
                    case self::LOAD:
                    case self::LOCAL:
                    case self::LOOKUP:
                    case self::MANAGEMENT:
                    case self::MAP:
                    case self::MATCH:
                    case self::MERGE:
                    case self::NAME:
                    case self::NAMES:
                    case self::NAN:
                    case self::NEW:
                    case self::NODE:
                    case self::NODETACH:
                    case self::NODES:
                    case self::NONE:
                    case self::NORMALIZE:
                    case self::NOTHING:
                    case self::NOWAIT:
                    case self::OF:
                    case self::OFFSET:
                    case self::ON:
                    case self::ONLY:
                    case self::OPTIONAL:
                    case self::OPTIONS:
                    case self::OPTION:
                    case self::OR:
                    case self::ORDER:
                    case self::PASSWORD:
                    case self::PASSWORDS:
                    case self::PATH:
                    case self::PATHS:
                    case self::PLAINTEXT:
                    case self::POINT:
                    case self::POPULATED:
                    case self::PRIMARY:
                    case self::PRIMARIES:
                    case self::PRIVILEGE:
                    case self::PRIVILEGES:
                    case self::PROCEDURE:
                    case self::PROCEDURES:
                    case self::PROPERTIES:
                    case self::PROPERTY:
                    case self::PROVIDER:
                    case self::PROVIDERS:
                    case self::RANGE:
                    case self::READ:
                    case self::REALLOCATE:
                    case self::REDUCE:
                    case self::RENAME:
                    case self::REL:
                    case self::RELATIONSHIP:
                    case self::RELATIONSHIPS:
                    case self::REMOVE:
                    case self::REPEATABLE:
                    case self::REPLACE:
                    case self::REPORT:
                    case self::REQUIRE:
                    case self::REQUIRED:
                    case self::RESTRICT:
                    case self::RETURN:
                    case self::REVOKE:
                    case self::ROLE:
                    case self::ROLES:
                    case self::ROW:
                    case self::ROWS:
                    case self::SCAN:
                    case self::SEC:
                    case self::SECOND:
                    case self::SECONDARY:
                    case self::SECONDARIES:
                    case self::SECONDS:
                    case self::SEEK:
                    case self::SERVER:
                    case self::SERVERS:
                    case self::SET:
                    case self::SETTING:
                    case self::SETTINGS:
                    case self::SHORTEST_PATH:
                    case self::SHORTEST:
                    case self::SHOW:
                    case self::SIGNED:
                    case self::SINGLE:
                    case self::SKIPROWS:
                    case self::START:
                    case self::STARTS:
                    case self::STATUS:
                    case self::STOP:
                    case self::STRING:
                    case self::SUPPORTED:
                    case self::SUSPENDED:
                    case self::TARGET:
                    case self::TERMINATE:
                    case self::TEXT:
                    case self::THEN:
                    case self::TIME:
                    case self::TIMESTAMP:
                    case self::TIMEZONE:
                    case self::TO:
                    case self::TOPOLOGY:
                    case self::TRAILING:
                    case self::TRANSACTION:
                    case self::TRANSACTIONS:
                    case self::TRAVERSE:
                    case self::TRIM:
                    case self::TRUE:
                    case self::TYPE:
                    case self::TYPES:
                    case self::UNION:
                    case self::UNIQUE:
                    case self::UNIQUENESS:
                    case self::UNWIND:
                    case self::URL:
                    case self::USE:
                    case self::USER:
                    case self::USERS:
                    case self::USING:
                    case self::VALUE:
                    case self::VARCHAR:
                    case self::VECTOR:
                    case self::VERTEX:
                    case self::WAIT:
                    case self::WHEN:
                    case self::WHERE:
                    case self::WITH:
                    case self::WITHOUT:
                    case self::WRITE:
                    case self::XOR:
                    case self::YIELD:
                    case self::ZONE:
                    case self::ZONED:
                    case self::IDENTIFIER:
                        $localContext = new Context\LabelNameIsContext($localContext);
                        $this->enterOuterAlt($localContext, 3);
                        $this->setState(418);
                        $this->symbolicLabelNameString();
                        break;

                    default:
                        throw new NoViableAltException($this);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function expression(): Context\ExpressionContext
        {
            $localContext = new Context\ExpressionContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 78, self::RULE_expression);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(421);
                $this->expression11();
                $this->setState(426);
                $this->errorHandler->sync($this);

                $_la = $this->input->LA(1);
                while (self::OR === $_la) {
                    $this->setState(422);
                    $this->match(self::OR);
                    $this->setState(423);
                    $this->expression11();
                    $this->setState(428);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function expression11(): Context\Expression11Context
        {
            $localContext = new Context\Expression11Context($this->ctx, $this->getState());

            $this->enterRule($localContext, 80, self::RULE_expression11);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(429);
                $this->expression10();
                $this->setState(434);
                $this->errorHandler->sync($this);

                $_la = $this->input->LA(1);
                while (self::XOR === $_la) {
                    $this->setState(430);
                    $this->match(self::XOR);
                    $this->setState(431);
                    $this->expression10();
                    $this->setState(436);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function expression10(): Context\Expression10Context
        {
            $localContext = new Context\Expression10Context($this->ctx, $this->getState());

            $this->enterRule($localContext, 82, self::RULE_expression10);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(437);
                $this->expression9();
                $this->setState(442);
                $this->errorHandler->sync($this);

                $_la = $this->input->LA(1);
                while (self::AND === $_la) {
                    $this->setState(438);
                    $this->match(self::AND);
                    $this->setState(439);
                    $this->expression9();
                    $this->setState(444);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function expression9(): Context\Expression9Context
        {
            $localContext = new Context\Expression9Context($this->ctx, $this->getState());

            $this->enterRule($localContext, 84, self::RULE_expression9);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(448);
                $this->errorHandler->sync($this);

                $alt = $this->getInterpreter()->adaptivePredict($this->input, 49, $this->ctx);

                while (2 !== $alt && ATN::INVALID_ALT_NUMBER !== $alt) {
                    if (1 === $alt) {
                        $this->setState(445);
                        $this->match(self::NOT);
                    }

                    $this->setState(450);
                    $this->errorHandler->sync($this);

                    $alt = $this->getInterpreter()->adaptivePredict($this->input, 49, $this->ctx);
                }
                $this->setState(451);
                $this->expression8();
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function expression8(): Context\Expression8Context
        {
            $localContext = new Context\Expression8Context($this->ctx, $this->getState());

            $this->enterRule($localContext, 86, self::RULE_expression8);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(453);
                $this->expression7();
                $this->setState(458);
                $this->errorHandler->sync($this);

                $_la = $this->input->LA(1);
                while (((($_la - 96) & ~0x3F) === 0 && ((1 << ($_la - 96)) & -9151032967823097855) !== 0) || self::NEQ === $_la) {
                    $this->setState(454);

                    $_la = $this->input->LA(1);

                    if (!(((($_la - 96) & ~0x3F) === 0 && ((1 << ($_la - 96)) & -9151032967823097855) !== 0) || self::NEQ === $_la)) {
                        $this->errorHandler->recoverInline($this);
                    } else {
                        if (Token::EOF === $this->input->LA(1)) {
                            $this->matchedEOF = true;
                        }

                        $this->errorHandler->reportMatch($this);
                        $this->consume();
                    }
                    $this->setState(455);
                    $this->expression7();
                    $this->setState(460);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function expression7(): Context\Expression7Context
        {
            $localContext = new Context\Expression7Context($this->ctx, $this->getState());

            $this->enterRule($localContext, 88, self::RULE_expression7);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(461);
                $this->expression6();
                $this->setState(463);
                $this->errorHandler->sync($this);
                $_la = $this->input->LA(1);

                if (self::COLONCOLON === $_la || self::CONTAINS === $_la || ((($_la - 95) & ~0x3F) === 0 && ((1 << ($_la - 95)) & 1103806595073) !== 0) || self::REGEQ === $_la || self::STARTS === $_la) {
                    $this->setState(462);
                    $this->comparisonExpression6();
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function comparisonExpression6(): Context\ComparisonExpression6Context
        {
            $localContext = new Context\ComparisonExpression6Context($this->ctx, $this->getState());

            $this->enterRule($localContext, 90, self::RULE_comparisonExpression6);

            try {
                $this->setState(497);
                $this->errorHandler->sync($this);

                switch ($this->getInterpreter()->adaptivePredict($this->input, 58, $this->ctx)) {
                    case 1:
                        $localContext = new Context\StringAndListComparisonContext($localContext);
                        $this->enterOuterAlt($localContext, 1);
                        $this->setState(472);
                        $this->errorHandler->sync($this);

                        switch ($this->input->LA(1)) {
                            case self::REGEQ:
                                $this->setState(465);
                                $this->match(self::REGEQ);
                                break;

                            case self::STARTS:
                                $this->setState(466);
                                $this->match(self::STARTS);
                                $this->setState(467);
                                $this->match(self::WITH);
                                break;

                            case self::ENDS:
                                $this->setState(468);
                                $this->match(self::ENDS);
                                $this->setState(469);
                                $this->match(self::WITH);
                                break;

                            case self::CONTAINS:
                                $this->setState(470);
                                $this->match(self::CONTAINS);
                                break;

                            case self::IN:
                                $this->setState(471);
                                $this->match(self::IN);
                                break;

                            default:
                                throw new NoViableAltException($this);
                        }
                        $this->setState(474);
                        $this->expression6();
                        break;

                    case 2:
                        $localContext = new Context\NullComparisonContext($localContext);
                        $this->enterOuterAlt($localContext, 2);
                        $this->setState(475);
                        $this->match(self::IS);
                        $this->setState(477);
                        $this->errorHandler->sync($this);
                        $_la = $this->input->LA(1);

                        if (self::NOT === $_la) {
                            $this->setState(476);
                            $this->match(self::NOT);
                        }
                        $this->setState(479);
                        $this->match(self::NULL);
                        break;

                    case 3:
                        $localContext = new Context\TypeComparisonContext($localContext);
                        $this->enterOuterAlt($localContext, 3);
                        $this->setState(486);
                        $this->errorHandler->sync($this);

                        switch ($this->input->LA(1)) {
                            case self::IS:
                                $this->setState(480);
                                $this->match(self::IS);
                                $this->setState(482);
                                $this->errorHandler->sync($this);
                                $_la = $this->input->LA(1);

                                if (self::NOT === $_la) {
                                    $this->setState(481);
                                    $this->match(self::NOT);
                                }
                                $this->setState(484);

                                $_la = $this->input->LA(1);

                                if (!(self::COLONCOLON === $_la || self::TYPED === $_la)) {
                                    $this->errorHandler->recoverInline($this);
                                } else {
                                    if (Token::EOF === $this->input->LA(1)) {
                                        $this->matchedEOF = true;
                                    }

                                    $this->errorHandler->reportMatch($this);
                                    $this->consume();
                                }
                                break;

                            case self::COLONCOLON:
                                $this->setState(485);
                                $this->match(self::COLONCOLON);
                                break;

                            default:
                                throw new NoViableAltException($this);
                        }
                        $this->setState(488);
                        $this->type();
                        break;

                    case 4:
                        $localContext = new Context\NormalFormComparisonContext($localContext);
                        $this->enterOuterAlt($localContext, 4);
                        $this->setState(489);
                        $this->match(self::IS);
                        $this->setState(491);
                        $this->errorHandler->sync($this);
                        $_la = $this->input->LA(1);

                        if (self::NOT === $_la) {
                            $this->setState(490);
                            $this->match(self::NOT);
                        }
                        $this->setState(494);
                        $this->errorHandler->sync($this);
                        $_la = $this->input->LA(1);

                        if ((($_la - 164) & ~0x3F) === 0 && ((1 << ($_la - 164)) & 15) !== 0) {
                            $this->setState(493);
                            $this->normalForm();
                        }
                        $this->setState(496);
                        $this->match(self::NORMALIZED);
                        break;
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function normalForm(): Context\NormalFormContext
        {
            $localContext = new Context\NormalFormContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 92, self::RULE_normalForm);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(499);

                $_la = $this->input->LA(1);

                if (!((($_la - 164) & ~0x3F) === 0 && ((1 << ($_la - 164)) & 15) !== 0)) {
                    $this->errorHandler->recoverInline($this);
                } else {
                    if (Token::EOF === $this->input->LA(1)) {
                        $this->matchedEOF = true;
                    }

                    $this->errorHandler->reportMatch($this);
                    $this->consume();
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function expression6(): Context\Expression6Context
        {
            $localContext = new Context\Expression6Context($this->ctx, $this->getState());

            $this->enterRule($localContext, 94, self::RULE_expression6);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(501);
                $this->expression5();
                $this->setState(506);
                $this->errorHandler->sync($this);

                $_la = $this->input->LA(1);
                while (self::DOUBLEBAR === $_la || self::MINUS === $_la || self::PLUS === $_la) {
                    $this->setState(502);

                    $_la = $this->input->LA(1);

                    if (!(self::DOUBLEBAR === $_la || self::MINUS === $_la || self::PLUS === $_la)) {
                        $this->errorHandler->recoverInline($this);
                    } else {
                        if (Token::EOF === $this->input->LA(1)) {
                            $this->matchedEOF = true;
                        }

                        $this->errorHandler->reportMatch($this);
                        $this->consume();
                    }
                    $this->setState(503);
                    $this->expression5();
                    $this->setState(508);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function expression5(): Context\Expression5Context
        {
            $localContext = new Context\Expression5Context($this->ctx, $this->getState());

            $this->enterRule($localContext, 96, self::RULE_expression5);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(509);
                $this->expression4();
                $this->setState(514);
                $this->errorHandler->sync($this);

                $_la = $this->input->LA(1);
                while (self::DIVIDE === $_la || self::PERCENT === $_la || self::TIMES === $_la) {
                    $this->setState(510);

                    $_la = $this->input->LA(1);

                    if (!(self::DIVIDE === $_la || self::PERCENT === $_la || self::TIMES === $_la)) {
                        $this->errorHandler->recoverInline($this);
                    } else {
                        if (Token::EOF === $this->input->LA(1)) {
                            $this->matchedEOF = true;
                        }

                        $this->errorHandler->reportMatch($this);
                        $this->consume();
                    }
                    $this->setState(511);
                    $this->expression4();
                    $this->setState(516);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function expression4(): Context\Expression4Context
        {
            $localContext = new Context\Expression4Context($this->ctx, $this->getState());

            $this->enterRule($localContext, 98, self::RULE_expression4);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(517);
                $this->expression3();
                $this->setState(522);
                $this->errorHandler->sync($this);

                $_la = $this->input->LA(1);
                while (self::POW === $_la) {
                    $this->setState(518);
                    $this->match(self::POW);
                    $this->setState(519);
                    $this->expression3();
                    $this->setState(524);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function expression3(): Context\Expression3Context
        {
            $localContext = new Context\Expression3Context($this->ctx, $this->getState());

            $this->enterRule($localContext, 100, self::RULE_expression3);

            try {
                $this->setState(528);
                $this->errorHandler->sync($this);

                switch ($this->getInterpreter()->adaptivePredict($this->input, 62, $this->ctx)) {
                    case 1:
                        $this->enterOuterAlt($localContext, 1);
                        $this->setState(525);
                        $this->expression2();
                        break;

                    case 2:
                        $this->enterOuterAlt($localContext, 2);
                        $this->setState(526);

                        $_la = $this->input->LA(1);

                        if (!(self::MINUS === $_la || self::PLUS === $_la)) {
                            $this->errorHandler->recoverInline($this);
                        } else {
                            if (Token::EOF === $this->input->LA(1)) {
                                $this->matchedEOF = true;
                            }

                            $this->errorHandler->reportMatch($this);
                            $this->consume();
                        }
                        $this->setState(527);
                        $this->expression2();
                        break;
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function expression2(): Context\Expression2Context
        {
            $localContext = new Context\Expression2Context($this->ctx, $this->getState());

            $this->enterRule($localContext, 102, self::RULE_expression2);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(530);
                $this->expression1();
                $this->setState(534);
                $this->errorHandler->sync($this);

                $alt = $this->getInterpreter()->adaptivePredict($this->input, 63, $this->ctx);

                while (2 !== $alt && ATN::INVALID_ALT_NUMBER !== $alt) {
                    if (1 === $alt) {
                        $this->setState(531);
                        $this->postFix();
                    }

                    $this->setState(536);
                    $this->errorHandler->sync($this);

                    $alt = $this->getInterpreter()->adaptivePredict($this->input, 63, $this->ctx);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function postFix(): Context\PostFixContext
        {
            $localContext = new Context\PostFixContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 104, self::RULE_postFix);

            try {
                $this->setState(552);
                $this->errorHandler->sync($this);

                switch ($this->getInterpreter()->adaptivePredict($this->input, 66, $this->ctx)) {
                    case 1:
                        $localContext = new Context\PropertyPostfixContext($localContext);
                        $this->enterOuterAlt($localContext, 1);
                        $this->setState(537);
                        $this->property();
                        break;

                    case 2:
                        $localContext = new Context\LabelPostfixContext($localContext);
                        $this->enterOuterAlt($localContext, 2);
                        $this->setState(538);
                        $this->labelExpression();
                        break;

                    case 3:
                        $localContext = new Context\IndexPostfixContext($localContext);
                        $this->enterOuterAlt($localContext, 3);
                        $this->setState(539);
                        $this->match(self::LBRACKET);
                        $this->setState(540);
                        $this->expression();
                        $this->setState(541);
                        $this->match(self::RBRACKET);
                        break;

                    case 4:
                        $localContext = new Context\RangePostfixContext($localContext);
                        $this->enterOuterAlt($localContext, 4);
                        $this->setState(543);
                        $this->match(self::LBRACKET);
                        $this->setState(545);
                        $this->errorHandler->sync($this);
                        $_la = $this->input->LA(1);

                        if ((($_la & ~0x3F) === 0 && ((1 << $_la) & -123145839181840) !== 0) || ((($_la - 64) & ~0x3F) === 0 && ((1 << ($_la - 64)) & -73183498239983617) !== 0) || ((($_la - 128) & ~0x3F) === 0 && ((1 << ($_la - 128)) & -7533064193) !== 0) || ((($_la - 192) & ~0x3F) === 0 && ((1 << ($_la - 192)) & -565148994306085) !== 0) || ((($_la - 256) & ~0x3F) === 0 && ((1 << ($_la - 256)) & 140737488354815) !== 0)) {
                            $this->setState(544);
                            $localContext->fromExp = $this->expression();
                        }
                        $this->setState(547);
                        $this->match(self::DOTDOT);
                        $this->setState(549);
                        $this->errorHandler->sync($this);
                        $_la = $this->input->LA(1);

                        if ((($_la & ~0x3F) === 0 && ((1 << $_la) & -123145839181840) !== 0) || ((($_la - 64) & ~0x3F) === 0 && ((1 << ($_la - 64)) & -73183498239983617) !== 0) || ((($_la - 128) & ~0x3F) === 0 && ((1 << ($_la - 128)) & -7533064193) !== 0) || ((($_la - 192) & ~0x3F) === 0 && ((1 << ($_la - 192)) & -565148994306085) !== 0) || ((($_la - 256) & ~0x3F) === 0 && ((1 << ($_la - 256)) & 140737488354815) !== 0)) {
                            $this->setState(548);
                            $localContext->toExp = $this->expression();
                        }
                        $this->setState(551);
                        $this->match(self::RBRACKET);
                        break;
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function property(): Context\PropertyContext
        {
            $localContext = new Context\PropertyContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 106, self::RULE_property);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(554);
                $this->match(self::DOT);
                $this->setState(555);
                $this->propertyKeyName();
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function expression1(): Context\Expression1Context
        {
            $localContext = new Context\Expression1Context($this->ctx, $this->getState());

            $this->enterRule($localContext, 108, self::RULE_expression1);

            try {
                $this->setState(561);
                $this->errorHandler->sync($this);

                switch ($this->getInterpreter()->adaptivePredict($this->input, 67, $this->ctx)) {
                    case 1:
                        $this->enterOuterAlt($localContext, 1);
                        $this->setState(557);
                        $this->literal();
                        break;

                    case 2:
                        $this->enterOuterAlt($localContext, 2);
                        $this->setState(558);
                        $this->parameter('ANY');
                        break;

                    case 3:
                        $this->enterOuterAlt($localContext, 3);
                        $this->setState(559);
                        $this->parenthesizedExpression();
                        break;

                    case 4:
                        $this->enterOuterAlt($localContext, 4);
                        $this->setState(560);
                        $this->variable();
                        break;
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function literal(): Context\LiteralContext
        {
            $localContext = new Context\LiteralContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 110, self::RULE_literal);

            try {
                $this->setState(572);
                $this->errorHandler->sync($this);

                switch ($this->input->LA(1)) {
                    case self::DECIMAL_DOUBLE:
                    case self::UNSIGNED_DECIMAL_INTEGER:
                    case self::UNSIGNED_HEX_INTEGER:
                    case self::UNSIGNED_OCTAL_INTEGER:
                    case self::MINUS:
                        $localContext = new Context\NummericLiteralContext($localContext);
                        $this->enterOuterAlt($localContext, 1);
                        $this->setState(563);
                        $this->numberLiteral();
                        break;

                    case self::STRING_LITERAL1:
                    case self::STRING_LITERAL2:
                        $localContext = new Context\StringsLiteralContext($localContext);
                        $this->enterOuterAlt($localContext, 2);
                        $this->setState(564);
                        $this->stringLiteral();
                        break;

                    case self::LCURLY:
                        $localContext = new Context\OtherLiteralContext($localContext);
                        $this->enterOuterAlt($localContext, 3);
                        $this->setState(565);
                        $this->map();
                        break;

                    case self::TRUE:
                        $localContext = new Context\BooleanLiteralContext($localContext);
                        $this->enterOuterAlt($localContext, 4);
                        $this->setState(566);
                        $this->match(self::TRUE);
                        break;

                    case self::FALSE:
                        $localContext = new Context\BooleanLiteralContext($localContext);
                        $this->enterOuterAlt($localContext, 5);
                        $this->setState(567);
                        $this->match(self::FALSE);
                        break;

                    case self::INF:
                        $localContext = new Context\KeywordLiteralContext($localContext);
                        $this->enterOuterAlt($localContext, 6);
                        $this->setState(568);
                        $this->match(self::INF);
                        break;

                    case self::INFINITY:
                        $localContext = new Context\KeywordLiteralContext($localContext);
                        $this->enterOuterAlt($localContext, 7);
                        $this->setState(569);
                        $this->match(self::INFINITY);
                        break;

                    case self::NAN:
                        $localContext = new Context\KeywordLiteralContext($localContext);
                        $this->enterOuterAlt($localContext, 8);
                        $this->setState(570);
                        $this->match(self::NAN);
                        break;

                    case self::NULL:
                        $localContext = new Context\KeywordLiteralContext($localContext);
                        $this->enterOuterAlt($localContext, 9);
                        $this->setState(571);
                        $this->match(self::NULL);
                        break;

                    default:
                        throw new NoViableAltException($this);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function parenthesizedExpression(): Context\ParenthesizedExpressionContext
        {
            $localContext = new Context\ParenthesizedExpressionContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 112, self::RULE_parenthesizedExpression);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(574);
                $this->match(self::LPAREN);
                $this->setState(575);
                $this->expression();
                $this->setState(576);
                $this->match(self::RPAREN);
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function numberLiteral(): Context\NumberLiteralContext
        {
            $localContext = new Context\NumberLiteralContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 114, self::RULE_numberLiteral);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(579);
                $this->errorHandler->sync($this);
                $_la = $this->input->LA(1);

                if (self::MINUS === $_la) {
                    $this->setState(578);
                    $this->match(self::MINUS);
                }
                $this->setState(581);

                $_la = $this->input->LA(1);

                if (!(($_la & ~0x3F) === 0 && ((1 << $_la) & 240) !== 0)) {
                    $this->errorHandler->recoverInline($this);
                } else {
                    if (Token::EOF === $this->input->LA(1)) {
                        $this->matchedEOF = true;
                    }

                    $this->errorHandler->reportMatch($this);
                    $this->consume();
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function propertyKeyName(): Context\PropertyKeyNameContext
        {
            $localContext = new Context\PropertyKeyNameContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 116, self::RULE_propertyKeyName);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(583);
                $this->symbolicNameString();
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function parameter(string $paramType): Context\ParameterContext
        {
            $localContext = new Context\ParameterContext($this->ctx, $this->getState(), $paramType);

            $this->enterRule($localContext, 118, self::RULE_parameter);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(585);
                $this->match(self::DOLLAR);
                $this->setState(586);
                $this->parameterName($paramType);
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function parameterName(string $paramType): Context\ParameterNameContext
        {
            $localContext = new Context\ParameterNameContext($this->ctx, $this->getState(), $paramType);

            $this->enterRule($localContext, 120, self::RULE_parameterName);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(592);
                $this->errorHandler->sync($this);

                switch ($this->input->LA(1)) {
                    case self::ESCAPED_SYMBOLIC_NAME:
                    case self::ACCESS:
                    case self::ACTIVE:
                    case self::ADMIN:
                    case self::ADMINISTRATOR:
                    case self::ALIAS:
                    case self::ALIASES:
                    case self::ALL_SHORTEST_PATHS:
                    case self::ALL:
                    case self::ALTER:
                    case self::AND:
                    case self::ANY:
                    case self::ARRAY:
                    case self::AS:
                    case self::ASC:
                    case self::ASCENDING:
                    case self::ASSIGN:
                    case self::AT:
                    case self::AUTH:
                    case self::BINDINGS:
                    case self::BOOL:
                    case self::BOOLEAN:
                    case self::BOOSTED:
                    case self::BOTH:
                    case self::BREAK:
                    case self::BUILT:
                    case self::BY:
                    case self::CALL:
                    case self::CASCADE:
                    case self::CASE:
                    case self::CHANGE:
                    case self::CIDR:
                    case self::COLLECT:
                    case self::COMMAND:
                    case self::COMMANDS:
                    case self::COMPOSITE:
                    case self::CONCURRENT:
                    case self::CONSTRAINT:
                    case self::CONSTRAINTS:
                    case self::CONTAINS:
                    case self::COPY:
                    case self::CONTINUE:
                    case self::COUNT:
                    case self::CREATE:
                    case self::CSV:
                    case self::CURRENT:
                    case self::DATA:
                    case self::DATABASE:
                    case self::DATABASES:
                    case self::DATE:
                    case self::DATETIME:
                    case self::DBMS:
                    case self::DEALLOCATE:
                    case self::DEFAULT:
                    case self::DEFINED:
                    case self::DELETE:
                    case self::DENY:
                    case self::DESC:
                    case self::DESCENDING:
                    case self::DESTROY:
                    case self::DETACH:
                    case self::DIFFERENT:
                    case self::DISTINCT:
                    case self::DRIVER:
                    case self::DROP:
                    case self::DRYRUN:
                    case self::DUMP:
                    case self::DURATION:
                    case self::EACH:
                    case self::EDGE:
                    case self::ENABLE:
                    case self::ELEMENT:
                    case self::ELEMENTS:
                    case self::ELSE:
                    case self::ENCRYPTED:
                    case self::END:
                    case self::ENDS:
                    case self::EXECUTABLE:
                    case self::EXECUTE:
                    case self::EXIST:
                    case self::EXISTENCE:
                    case self::EXISTS:
                    case self::ERROR:
                    case self::FAIL:
                    case self::FALSE:
                    case self::FIELDTERMINATOR:
                    case self::FINISH:
                    case self::FLOAT:
                    case self::FOR:
                    case self::FOREACH:
                    case self::FROM:
                    case self::FULLTEXT:
                    case self::FUNCTION:
                    case self::FUNCTIONS:
                    case self::GRANT:
                    case self::GRAPH:
                    case self::GRAPHS:
                    case self::GROUP:
                    case self::GROUPS:
                    case self::HEADERS:
                    case self::HOME:
                    case self::ID:
                    case self::IF:
                    case self::IMPERSONATE:
                    case self::IMMUTABLE:
                    case self::IN:
                    case self::INDEX:
                    case self::INDEXES:
                    case self::INF:
                    case self::INFINITY:
                    case self::INSERT:
                    case self::INT:
                    case self::INTEGER:
                    case self::IS:
                    case self::JOIN:
                    case self::KEY:
                    case self::LABEL:
                    case self::LABELS:
                    case self::LEADING:
                    case self::LIMITROWS:
                    case self::LIST:
                    case self::LOAD:
                    case self::LOCAL:
                    case self::LOOKUP:
                    case self::MANAGEMENT:
                    case self::MAP:
                    case self::MATCH:
                    case self::MERGE:
                    case self::NAME:
                    case self::NAMES:
                    case self::NAN:
                    case self::NFC:
                    case self::NFD:
                    case self::NFKC:
                    case self::NFKD:
                    case self::NEW:
                    case self::NODE:
                    case self::NODETACH:
                    case self::NODES:
                    case self::NONE:
                    case self::NORMALIZE:
                    case self::NORMALIZED:
                    case self::NOT:
                    case self::NOTHING:
                    case self::NOWAIT:
                    case self::NULL:
                    case self::OF:
                    case self::OFFSET:
                    case self::ON:
                    case self::ONLY:
                    case self::OPTIONAL:
                    case self::OPTIONS:
                    case self::OPTION:
                    case self::OR:
                    case self::ORDER:
                    case self::PASSWORD:
                    case self::PASSWORDS:
                    case self::PATH:
                    case self::PATHS:
                    case self::PLAINTEXT:
                    case self::POINT:
                    case self::POPULATED:
                    case self::PRIMARY:
                    case self::PRIMARIES:
                    case self::PRIVILEGE:
                    case self::PRIVILEGES:
                    case self::PROCEDURE:
                    case self::PROCEDURES:
                    case self::PROPERTIES:
                    case self::PROPERTY:
                    case self::PROVIDER:
                    case self::PROVIDERS:
                    case self::RANGE:
                    case self::READ:
                    case self::REALLOCATE:
                    case self::REDUCE:
                    case self::RENAME:
                    case self::REL:
                    case self::RELATIONSHIP:
                    case self::RELATIONSHIPS:
                    case self::REMOVE:
                    case self::REPEATABLE:
                    case self::REPLACE:
                    case self::REPORT:
                    case self::REQUIRE:
                    case self::REQUIRED:
                    case self::RESTRICT:
                    case self::RETURN:
                    case self::REVOKE:
                    case self::ROLE:
                    case self::ROLES:
                    case self::ROW:
                    case self::ROWS:
                    case self::SCAN:
                    case self::SEC:
                    case self::SECOND:
                    case self::SECONDARY:
                    case self::SECONDARIES:
                    case self::SECONDS:
                    case self::SEEK:
                    case self::SERVER:
                    case self::SERVERS:
                    case self::SET:
                    case self::SETTING:
                    case self::SETTINGS:
                    case self::SHORTEST_PATH:
                    case self::SHORTEST:
                    case self::SHOW:
                    case self::SIGNED:
                    case self::SINGLE:
                    case self::SKIPROWS:
                    case self::START:
                    case self::STARTS:
                    case self::STATUS:
                    case self::STOP:
                    case self::STRING:
                    case self::SUPPORTED:
                    case self::SUSPENDED:
                    case self::TARGET:
                    case self::TERMINATE:
                    case self::TEXT:
                    case self::THEN:
                    case self::TIME:
                    case self::TIMESTAMP:
                    case self::TIMEZONE:
                    case self::TO:
                    case self::TOPOLOGY:
                    case self::TRAILING:
                    case self::TRANSACTION:
                    case self::TRANSACTIONS:
                    case self::TRAVERSE:
                    case self::TRIM:
                    case self::TRUE:
                    case self::TYPE:
                    case self::TYPED:
                    case self::TYPES:
                    case self::UNION:
                    case self::UNIQUE:
                    case self::UNIQUENESS:
                    case self::UNWIND:
                    case self::URL:
                    case self::USE:
                    case self::USER:
                    case self::USERS:
                    case self::USING:
                    case self::VALUE:
                    case self::VARCHAR:
                    case self::VECTOR:
                    case self::VERTEX:
                    case self::WAIT:
                    case self::WHEN:
                    case self::WHERE:
                    case self::WITH:
                    case self::WITHOUT:
                    case self::WRITE:
                    case self::XOR:
                    case self::YIELD:
                    case self::ZONE:
                    case self::ZONED:
                    case self::IDENTIFIER:
                        $this->setState(588);
                        $this->symbolicNameString();
                        break;

                    case self::UNSIGNED_DECIMAL_INTEGER:
                        $this->setState(589);
                        $this->match(self::UNSIGNED_DECIMAL_INTEGER);
                        break;

                    case self::UNSIGNED_OCTAL_INTEGER:
                        $this->setState(590);
                        $this->match(self::UNSIGNED_OCTAL_INTEGER);
                        break;

                    case self::EXTENDED_IDENTIFIER:
                        $this->setState(591);
                        $this->match(self::EXTENDED_IDENTIFIER);
                        break;

                    default:
                        throw new NoViableAltException($this);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function variable(): Context\VariableContext
        {
            $localContext = new Context\VariableContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 122, self::RULE_variable);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(594);
                $this->symbolicNameString();
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function type(): Context\TypeContext
        {
            $localContext = new Context\TypeContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 124, self::RULE_type);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(596);
                $this->typePart();
                $this->setState(601);
                $this->errorHandler->sync($this);

                $_la = $this->input->LA(1);
                while (self::BAR === $_la) {
                    $this->setState(597);
                    $this->match(self::BAR);
                    $this->setState(598);
                    $this->typePart();
                    $this->setState(603);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function typePart(): Context\TypePartContext
        {
            $localContext = new Context\TypePartContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 126, self::RULE_typePart);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(604);
                $this->typeName();
                $this->setState(606);
                $this->errorHandler->sync($this);
                $_la = $this->input->LA(1);

                if (self::EXCLAMATION_MARK === $_la || self::NOT === $_la) {
                    $this->setState(605);
                    $this->typeNullability();
                }
                $this->setState(611);
                $this->errorHandler->sync($this);

                $_la = $this->input->LA(1);
                while (self::ARRAY === $_la || self::LIST === $_la) {
                    $this->setState(608);
                    $this->typeListSuffix();
                    $this->setState(613);
                    $this->errorHandler->sync($this);
                    $_la = $this->input->LA(1);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function typeName(): Context\TypeNameContext
        {
            $localContext = new Context\TypeNameContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 128, self::RULE_typeName);

            try {
                $this->setState(679);
                $this->errorHandler->sync($this);

                switch ($this->input->LA(1)) {
                    case self::NOTHING:
                        $this->enterOuterAlt($localContext, 1);
                        $this->setState(614);
                        $this->match(self::NOTHING);
                        break;

                    case self::NULL:
                        $this->enterOuterAlt($localContext, 2);
                        $this->setState(615);
                        $this->match(self::NULL);
                        break;

                    case self::BOOL:
                        $this->enterOuterAlt($localContext, 3);
                        $this->setState(616);
                        $this->match(self::BOOL);
                        break;

                    case self::BOOLEAN:
                        $this->enterOuterAlt($localContext, 4);
                        $this->setState(617);
                        $this->match(self::BOOLEAN);
                        break;

                    case self::VARCHAR:
                        $this->enterOuterAlt($localContext, 5);
                        $this->setState(618);
                        $this->match(self::VARCHAR);
                        break;

                    case self::STRING:
                        $this->enterOuterAlt($localContext, 6);
                        $this->setState(619);
                        $this->match(self::STRING);
                        break;

                    case self::INT:
                        $this->enterOuterAlt($localContext, 7);
                        $this->setState(620);
                        $this->match(self::INT);
                        break;

                    case self::INTEGER:
                    case self::SIGNED:
                        $this->enterOuterAlt($localContext, 8);
                        $this->setState(622);
                        $this->errorHandler->sync($this);
                        $_la = $this->input->LA(1);

                        if (self::SIGNED === $_la) {
                            $this->setState(621);
                            $this->match(self::SIGNED);
                        }
                        $this->setState(624);
                        $this->match(self::INTEGER);
                        break;

                    case self::FLOAT:
                        $this->enterOuterAlt($localContext, 9);
                        $this->setState(625);
                        $this->match(self::FLOAT);
                        break;

                    case self::DATE:
                        $this->enterOuterAlt($localContext, 10);
                        $this->setState(626);
                        $this->match(self::DATE);
                        break;

                    case self::LOCAL:
                        $this->enterOuterAlt($localContext, 11);
                        $this->setState(627);
                        $this->match(self::LOCAL);
                        $this->setState(628);

                        $_la = $this->input->LA(1);

                        if (!(self::DATETIME === $_la || self::TIME === $_la)) {
                            $this->errorHandler->recoverInline($this);
                        } else {
                            if (Token::EOF === $this->input->LA(1)) {
                                $this->matchedEOF = true;
                            }

                            $this->errorHandler->reportMatch($this);
                            $this->consume();
                        }
                        break;

                    case self::ZONED:
                        $this->enterOuterAlt($localContext, 12);
                        $this->setState(629);
                        $this->match(self::ZONED);
                        $this->setState(630);

                        $_la = $this->input->LA(1);

                        if (!(self::DATETIME === $_la || self::TIME === $_la)) {
                            $this->errorHandler->recoverInline($this);
                        } else {
                            if (Token::EOF === $this->input->LA(1)) {
                                $this->matchedEOF = true;
                            }

                            $this->errorHandler->reportMatch($this);
                            $this->consume();
                        }
                        break;

                    case self::TIME:
                        $this->enterOuterAlt($localContext, 13);
                        $this->setState(631);
                        $this->match(self::TIME);
                        $this->setState(632);

                        $_la = $this->input->LA(1);

                        if (!(self::WITH === $_la || self::WITHOUT === $_la)) {
                            $this->errorHandler->recoverInline($this);
                        } else {
                            if (Token::EOF === $this->input->LA(1)) {
                                $this->matchedEOF = true;
                            }

                            $this->errorHandler->reportMatch($this);
                            $this->consume();
                        }
                        $this->setState(636);
                        $this->errorHandler->sync($this);

                        switch ($this->input->LA(1)) {
                            case self::TIMEZONE:
                                $this->setState(633);
                                $this->match(self::TIMEZONE);
                                break;

                            case self::TIME:
                                $this->setState(634);
                                $this->match(self::TIME);
                                $this->setState(635);
                                $this->match(self::ZONE);
                                break;

                            default:
                                throw new NoViableAltException($this);
                        }
                        break;

                    case self::TIMESTAMP:
                        $this->enterOuterAlt($localContext, 14);
                        $this->setState(638);
                        $this->match(self::TIMESTAMP);
                        $this->setState(639);

                        $_la = $this->input->LA(1);

                        if (!(self::WITH === $_la || self::WITHOUT === $_la)) {
                            $this->errorHandler->recoverInline($this);
                        } else {
                            if (Token::EOF === $this->input->LA(1)) {
                                $this->matchedEOF = true;
                            }

                            $this->errorHandler->reportMatch($this);
                            $this->consume();
                        }
                        $this->setState(643);
                        $this->errorHandler->sync($this);

                        switch ($this->input->LA(1)) {
                            case self::TIMEZONE:
                                $this->setState(640);
                                $this->match(self::TIMEZONE);
                                break;

                            case self::TIME:
                                $this->setState(641);
                                $this->match(self::TIME);
                                $this->setState(642);
                                $this->match(self::ZONE);
                                break;

                            default:
                                throw new NoViableAltException($this);
                        }
                        break;

                    case self::DURATION:
                        $this->enterOuterAlt($localContext, 15);
                        $this->setState(645);
                        $this->match(self::DURATION);
                        break;

                    case self::POINT:
                        $this->enterOuterAlt($localContext, 16);
                        $this->setState(646);
                        $this->match(self::POINT);
                        break;

                    case self::NODE:
                        $this->enterOuterAlt($localContext, 17);
                        $this->setState(647);
                        $this->match(self::NODE);
                        break;

                    case self::VERTEX:
                        $this->enterOuterAlt($localContext, 18);
                        $this->setState(648);
                        $this->match(self::VERTEX);
                        break;

                    case self::RELATIONSHIP:
                        $this->enterOuterAlt($localContext, 19);
                        $this->setState(649);
                        $this->match(self::RELATIONSHIP);
                        break;

                    case self::EDGE:
                        $this->enterOuterAlt($localContext, 20);
                        $this->setState(650);
                        $this->match(self::EDGE);
                        break;

                    case self::MAP:
                        $this->enterOuterAlt($localContext, 21);
                        $this->setState(651);
                        $this->match(self::MAP);
                        break;

                    case self::ARRAY:
                    case self::LIST:
                        $this->enterOuterAlt($localContext, 22);
                        $this->setState(652);

                        $_la = $this->input->LA(1);

                        if (!(self::ARRAY === $_la || self::LIST === $_la)) {
                            $this->errorHandler->recoverInline($this);
                        } else {
                            if (Token::EOF === $this->input->LA(1)) {
                                $this->matchedEOF = true;
                            }

                            $this->errorHandler->reportMatch($this);
                            $this->consume();
                        }
                        $this->setState(653);
                        $this->match(self::LT);
                        $this->setState(654);
                        $this->type();
                        $this->setState(655);
                        $this->match(self::GT);
                        break;

                    case self::PATH:
                        $this->enterOuterAlt($localContext, 23);
                        $this->setState(657);
                        $this->match(self::PATH);
                        break;

                    case self::PATHS:
                        $this->enterOuterAlt($localContext, 24);
                        $this->setState(658);
                        $this->match(self::PATHS);
                        break;

                    case self::PROPERTY:
                        $this->enterOuterAlt($localContext, 25);
                        $this->setState(659);
                        $this->match(self::PROPERTY);
                        $this->setState(660);
                        $this->match(self::VALUE);
                        break;

                    case self::ANY:
                        $this->enterOuterAlt($localContext, 26);
                        $this->setState(661);
                        $this->match(self::ANY);
                        $this->setState(677);
                        $this->errorHandler->sync($this);

                        switch ($this->getInterpreter()->adaptivePredict($this->input, 78, $this->ctx)) {
                            case 1:
                                $this->setState(662);
                                $this->match(self::NODE);
                                break;

                            case 2:
                                $this->setState(663);
                                $this->match(self::VERTEX);
                                break;

                            case 3:
                                $this->setState(664);
                                $this->match(self::RELATIONSHIP);
                                break;

                            case 4:
                                $this->setState(665);
                                $this->match(self::EDGE);
                                break;

                            case 5:
                                $this->setState(666);
                                $this->match(self::MAP);
                                break;

                            case 6:
                                $this->setState(667);
                                $this->match(self::PROPERTY);
                                $this->setState(668);
                                $this->match(self::VALUE);
                                break;

                            case 7:
                                $this->setState(670);
                                $this->errorHandler->sync($this);
                                $_la = $this->input->LA(1);

                                if (self::VALUE === $_la) {
                                    $this->setState(669);
                                    $this->match(self::VALUE);
                                }
                                $this->setState(672);
                                $this->match(self::LT);
                                $this->setState(673);
                                $this->type();
                                $this->setState(674);
                                $this->match(self::GT);
                                break;

                            case 8:
                                $this->setState(676);
                                $this->match(self::VALUE);
                                break;
                        }
                        break;

                    default:
                        throw new NoViableAltException($this);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function typeNullability(): Context\TypeNullabilityContext
        {
            $localContext = new Context\TypeNullabilityContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 130, self::RULE_typeNullability);

            try {
                $this->setState(684);
                $this->errorHandler->sync($this);

                switch ($this->input->LA(1)) {
                    case self::NOT:
                        $this->enterOuterAlt($localContext, 1);
                        $this->setState(681);
                        $this->match(self::NOT);
                        $this->setState(682);
                        $this->match(self::NULL);
                        break;

                    case self::EXCLAMATION_MARK:
                        $this->enterOuterAlt($localContext, 2);
                        $this->setState(683);
                        $this->match(self::EXCLAMATION_MARK);
                        break;

                    default:
                        throw new NoViableAltException($this);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function typeListSuffix(): Context\TypeListSuffixContext
        {
            $localContext = new Context\TypeListSuffixContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 132, self::RULE_typeListSuffix);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(686);

                $_la = $this->input->LA(1);

                if (!(self::ARRAY === $_la || self::LIST === $_la)) {
                    $this->errorHandler->recoverInline($this);
                } else {
                    if (Token::EOF === $this->input->LA(1)) {
                        $this->matchedEOF = true;
                    }

                    $this->errorHandler->reportMatch($this);
                    $this->consume();
                }
                $this->setState(688);
                $this->errorHandler->sync($this);
                $_la = $this->input->LA(1);

                if (self::EXCLAMATION_MARK === $_la || self::NOT === $_la) {
                    $this->setState(687);
                    $this->typeNullability();
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function stringLiteral(): Context\StringLiteralContext
        {
            $localContext = new Context\StringLiteralContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 134, self::RULE_stringLiteral);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(690);

                $_la = $this->input->LA(1);

                if (!(self::STRING_LITERAL1 === $_la || self::STRING_LITERAL2 === $_la)) {
                    $this->errorHandler->recoverInline($this);
                } else {
                    if (Token::EOF === $this->input->LA(1)) {
                        $this->matchedEOF = true;
                    }

                    $this->errorHandler->reportMatch($this);
                    $this->consume();
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function map(): Context\MapContext
        {
            $localContext = new Context\MapContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 136, self::RULE_map);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(692);
                $this->match(self::LCURLY);
                $this->setState(706);
                $this->errorHandler->sync($this);
                $_la = $this->input->LA(1);

                if ((($_la & ~0x3F) === 0 && ((1 << $_la) & -123145839182848) !== 0) || ((($_la - 64) & ~0x3F) === 0 && ((1 << ($_la - 64)) & -73183498239987713) !== 0) || ((($_la - 128) & ~0x3F) === 0 && ((1 << ($_la - 128)) & -8078356481) !== 0) || ((($_la - 192) & ~0x3F) === 0 && ((1 << ($_la - 192)) & -565148994306087) !== 0) || ((($_la - 256) & ~0x3F) === 0 && ((1 << ($_la - 256)) & 140737488354815) !== 0)) {
                    $this->setState(693);
                    $this->propertyKeyName();
                    $this->setState(694);
                    $this->match(self::COLON);
                    $this->setState(695);
                    $this->expression();
                    $this->setState(703);
                    $this->errorHandler->sync($this);

                    $_la = $this->input->LA(1);
                    while (self::COMMA === $_la) {
                        $this->setState(696);
                        $this->match(self::COMMA);
                        $this->setState(697);
                        $this->propertyKeyName();
                        $this->setState(698);
                        $this->match(self::COLON);
                        $this->setState(699);
                        $this->expression();
                        $this->setState(705);
                        $this->errorHandler->sync($this);
                        $_la = $this->input->LA(1);
                    }
                }
                $this->setState(708);
                $this->match(self::RCURLY);
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function symbolicNameString(): Context\SymbolicNameStringContext
        {
            $localContext = new Context\SymbolicNameStringContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 138, self::RULE_symbolicNameString);

            try {
                $this->setState(712);
                $this->errorHandler->sync($this);

                switch ($this->input->LA(1)) {
                    case self::ESCAPED_SYMBOLIC_NAME:
                        $this->enterOuterAlt($localContext, 1);
                        $this->setState(710);
                        $this->escapedSymbolicNameString();
                        break;

                    case self::ACCESS:
                    case self::ACTIVE:
                    case self::ADMIN:
                    case self::ADMINISTRATOR:
                    case self::ALIAS:
                    case self::ALIASES:
                    case self::ALL_SHORTEST_PATHS:
                    case self::ALL:
                    case self::ALTER:
                    case self::AND:
                    case self::ANY:
                    case self::ARRAY:
                    case self::AS:
                    case self::ASC:
                    case self::ASCENDING:
                    case self::ASSIGN:
                    case self::AT:
                    case self::AUTH:
                    case self::BINDINGS:
                    case self::BOOL:
                    case self::BOOLEAN:
                    case self::BOOSTED:
                    case self::BOTH:
                    case self::BREAK:
                    case self::BUILT:
                    case self::BY:
                    case self::CALL:
                    case self::CASCADE:
                    case self::CASE:
                    case self::CHANGE:
                    case self::CIDR:
                    case self::COLLECT:
                    case self::COMMAND:
                    case self::COMMANDS:
                    case self::COMPOSITE:
                    case self::CONCURRENT:
                    case self::CONSTRAINT:
                    case self::CONSTRAINTS:
                    case self::CONTAINS:
                    case self::COPY:
                    case self::CONTINUE:
                    case self::COUNT:
                    case self::CREATE:
                    case self::CSV:
                    case self::CURRENT:
                    case self::DATA:
                    case self::DATABASE:
                    case self::DATABASES:
                    case self::DATE:
                    case self::DATETIME:
                    case self::DBMS:
                    case self::DEALLOCATE:
                    case self::DEFAULT:
                    case self::DEFINED:
                    case self::DELETE:
                    case self::DENY:
                    case self::DESC:
                    case self::DESCENDING:
                    case self::DESTROY:
                    case self::DETACH:
                    case self::DIFFERENT:
                    case self::DISTINCT:
                    case self::DRIVER:
                    case self::DROP:
                    case self::DRYRUN:
                    case self::DUMP:
                    case self::DURATION:
                    case self::EACH:
                    case self::EDGE:
                    case self::ENABLE:
                    case self::ELEMENT:
                    case self::ELEMENTS:
                    case self::ELSE:
                    case self::ENCRYPTED:
                    case self::END:
                    case self::ENDS:
                    case self::EXECUTABLE:
                    case self::EXECUTE:
                    case self::EXIST:
                    case self::EXISTENCE:
                    case self::EXISTS:
                    case self::ERROR:
                    case self::FAIL:
                    case self::FALSE:
                    case self::FIELDTERMINATOR:
                    case self::FINISH:
                    case self::FLOAT:
                    case self::FOR:
                    case self::FOREACH:
                    case self::FROM:
                    case self::FULLTEXT:
                    case self::FUNCTION:
                    case self::FUNCTIONS:
                    case self::GRANT:
                    case self::GRAPH:
                    case self::GRAPHS:
                    case self::GROUP:
                    case self::GROUPS:
                    case self::HEADERS:
                    case self::HOME:
                    case self::ID:
                    case self::IF:
                    case self::IMPERSONATE:
                    case self::IMMUTABLE:
                    case self::IN:
                    case self::INDEX:
                    case self::INDEXES:
                    case self::INF:
                    case self::INFINITY:
                    case self::INSERT:
                    case self::INT:
                    case self::INTEGER:
                    case self::IS:
                    case self::JOIN:
                    case self::KEY:
                    case self::LABEL:
                    case self::LABELS:
                    case self::LEADING:
                    case self::LIMITROWS:
                    case self::LIST:
                    case self::LOAD:
                    case self::LOCAL:
                    case self::LOOKUP:
                    case self::MANAGEMENT:
                    case self::MAP:
                    case self::MATCH:
                    case self::MERGE:
                    case self::NAME:
                    case self::NAMES:
                    case self::NAN:
                    case self::NFC:
                    case self::NFD:
                    case self::NFKC:
                    case self::NFKD:
                    case self::NEW:
                    case self::NODE:
                    case self::NODETACH:
                    case self::NODES:
                    case self::NONE:
                    case self::NORMALIZE:
                    case self::NORMALIZED:
                    case self::NOT:
                    case self::NOTHING:
                    case self::NOWAIT:
                    case self::NULL:
                    case self::OF:
                    case self::OFFSET:
                    case self::ON:
                    case self::ONLY:
                    case self::OPTIONAL:
                    case self::OPTIONS:
                    case self::OPTION:
                    case self::OR:
                    case self::ORDER:
                    case self::PASSWORD:
                    case self::PASSWORDS:
                    case self::PATH:
                    case self::PATHS:
                    case self::PLAINTEXT:
                    case self::POINT:
                    case self::POPULATED:
                    case self::PRIMARY:
                    case self::PRIMARIES:
                    case self::PRIVILEGE:
                    case self::PRIVILEGES:
                    case self::PROCEDURE:
                    case self::PROCEDURES:
                    case self::PROPERTIES:
                    case self::PROPERTY:
                    case self::PROVIDER:
                    case self::PROVIDERS:
                    case self::RANGE:
                    case self::READ:
                    case self::REALLOCATE:
                    case self::REDUCE:
                    case self::RENAME:
                    case self::REL:
                    case self::RELATIONSHIP:
                    case self::RELATIONSHIPS:
                    case self::REMOVE:
                    case self::REPEATABLE:
                    case self::REPLACE:
                    case self::REPORT:
                    case self::REQUIRE:
                    case self::REQUIRED:
                    case self::RESTRICT:
                    case self::RETURN:
                    case self::REVOKE:
                    case self::ROLE:
                    case self::ROLES:
                    case self::ROW:
                    case self::ROWS:
                    case self::SCAN:
                    case self::SEC:
                    case self::SECOND:
                    case self::SECONDARY:
                    case self::SECONDARIES:
                    case self::SECONDS:
                    case self::SEEK:
                    case self::SERVER:
                    case self::SERVERS:
                    case self::SET:
                    case self::SETTING:
                    case self::SETTINGS:
                    case self::SHORTEST_PATH:
                    case self::SHORTEST:
                    case self::SHOW:
                    case self::SIGNED:
                    case self::SINGLE:
                    case self::SKIPROWS:
                    case self::START:
                    case self::STARTS:
                    case self::STATUS:
                    case self::STOP:
                    case self::STRING:
                    case self::SUPPORTED:
                    case self::SUSPENDED:
                    case self::TARGET:
                    case self::TERMINATE:
                    case self::TEXT:
                    case self::THEN:
                    case self::TIME:
                    case self::TIMESTAMP:
                    case self::TIMEZONE:
                    case self::TO:
                    case self::TOPOLOGY:
                    case self::TRAILING:
                    case self::TRANSACTION:
                    case self::TRANSACTIONS:
                    case self::TRAVERSE:
                    case self::TRIM:
                    case self::TRUE:
                    case self::TYPE:
                    case self::TYPED:
                    case self::TYPES:
                    case self::UNION:
                    case self::UNIQUE:
                    case self::UNIQUENESS:
                    case self::UNWIND:
                    case self::URL:
                    case self::USE:
                    case self::USER:
                    case self::USERS:
                    case self::USING:
                    case self::VALUE:
                    case self::VARCHAR:
                    case self::VECTOR:
                    case self::VERTEX:
                    case self::WAIT:
                    case self::WHEN:
                    case self::WHERE:
                    case self::WITH:
                    case self::WITHOUT:
                    case self::WRITE:
                    case self::XOR:
                    case self::YIELD:
                    case self::ZONE:
                    case self::ZONED:
                    case self::IDENTIFIER:
                        $this->enterOuterAlt($localContext, 2);
                        $this->setState(711);
                        $this->unescapedSymbolicNameString();
                        break;

                    default:
                        throw new NoViableAltException($this);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function escapedSymbolicNameString(): Context\EscapedSymbolicNameStringContext
        {
            $localContext = new Context\EscapedSymbolicNameStringContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 140, self::RULE_escapedSymbolicNameString);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(714);
                $this->match(self::ESCAPED_SYMBOLIC_NAME);
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function unescapedSymbolicNameString(): Context\UnescapedSymbolicNameStringContext
        {
            $localContext = new Context\UnescapedSymbolicNameStringContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 142, self::RULE_unescapedSymbolicNameString);

            try {
                $this->setState(725);
                $this->errorHandler->sync($this);

                switch ($this->input->LA(1)) {
                    case self::ACCESS:
                    case self::ACTIVE:
                    case self::ADMIN:
                    case self::ADMINISTRATOR:
                    case self::ALIAS:
                    case self::ALIASES:
                    case self::ALL_SHORTEST_PATHS:
                    case self::ALL:
                    case self::ALTER:
                    case self::AND:
                    case self::ANY:
                    case self::ARRAY:
                    case self::AS:
                    case self::ASC:
                    case self::ASCENDING:
                    case self::ASSIGN:
                    case self::AT:
                    case self::AUTH:
                    case self::BINDINGS:
                    case self::BOOL:
                    case self::BOOLEAN:
                    case self::BOOSTED:
                    case self::BOTH:
                    case self::BREAK:
                    case self::BUILT:
                    case self::BY:
                    case self::CALL:
                    case self::CASCADE:
                    case self::CASE:
                    case self::CHANGE:
                    case self::CIDR:
                    case self::COLLECT:
                    case self::COMMAND:
                    case self::COMMANDS:
                    case self::COMPOSITE:
                    case self::CONCURRENT:
                    case self::CONSTRAINT:
                    case self::CONSTRAINTS:
                    case self::CONTAINS:
                    case self::COPY:
                    case self::CONTINUE:
                    case self::COUNT:
                    case self::CREATE:
                    case self::CSV:
                    case self::CURRENT:
                    case self::DATA:
                    case self::DATABASE:
                    case self::DATABASES:
                    case self::DATE:
                    case self::DATETIME:
                    case self::DBMS:
                    case self::DEALLOCATE:
                    case self::DEFAULT:
                    case self::DEFINED:
                    case self::DELETE:
                    case self::DENY:
                    case self::DESC:
                    case self::DESCENDING:
                    case self::DESTROY:
                    case self::DETACH:
                    case self::DIFFERENT:
                    case self::DISTINCT:
                    case self::DRIVER:
                    case self::DROP:
                    case self::DRYRUN:
                    case self::DUMP:
                    case self::DURATION:
                    case self::EACH:
                    case self::EDGE:
                    case self::ENABLE:
                    case self::ELEMENT:
                    case self::ELEMENTS:
                    case self::ELSE:
                    case self::ENCRYPTED:
                    case self::END:
                    case self::ENDS:
                    case self::EXECUTABLE:
                    case self::EXECUTE:
                    case self::EXIST:
                    case self::EXISTENCE:
                    case self::EXISTS:
                    case self::ERROR:
                    case self::FAIL:
                    case self::FALSE:
                    case self::FIELDTERMINATOR:
                    case self::FINISH:
                    case self::FLOAT:
                    case self::FOR:
                    case self::FOREACH:
                    case self::FROM:
                    case self::FULLTEXT:
                    case self::FUNCTION:
                    case self::FUNCTIONS:
                    case self::GRANT:
                    case self::GRAPH:
                    case self::GRAPHS:
                    case self::GROUP:
                    case self::GROUPS:
                    case self::HEADERS:
                    case self::HOME:
                    case self::ID:
                    case self::IF:
                    case self::IMPERSONATE:
                    case self::IMMUTABLE:
                    case self::IN:
                    case self::INDEX:
                    case self::INDEXES:
                    case self::INF:
                    case self::INFINITY:
                    case self::INSERT:
                    case self::INT:
                    case self::INTEGER:
                    case self::IS:
                    case self::JOIN:
                    case self::KEY:
                    case self::LABEL:
                    case self::LABELS:
                    case self::LEADING:
                    case self::LIMITROWS:
                    case self::LIST:
                    case self::LOAD:
                    case self::LOCAL:
                    case self::LOOKUP:
                    case self::MANAGEMENT:
                    case self::MAP:
                    case self::MATCH:
                    case self::MERGE:
                    case self::NAME:
                    case self::NAMES:
                    case self::NAN:
                    case self::NEW:
                    case self::NODE:
                    case self::NODETACH:
                    case self::NODES:
                    case self::NONE:
                    case self::NORMALIZE:
                    case self::NOTHING:
                    case self::NOWAIT:
                    case self::OF:
                    case self::OFFSET:
                    case self::ON:
                    case self::ONLY:
                    case self::OPTIONAL:
                    case self::OPTIONS:
                    case self::OPTION:
                    case self::OR:
                    case self::ORDER:
                    case self::PASSWORD:
                    case self::PASSWORDS:
                    case self::PATH:
                    case self::PATHS:
                    case self::PLAINTEXT:
                    case self::POINT:
                    case self::POPULATED:
                    case self::PRIMARY:
                    case self::PRIMARIES:
                    case self::PRIVILEGE:
                    case self::PRIVILEGES:
                    case self::PROCEDURE:
                    case self::PROCEDURES:
                    case self::PROPERTIES:
                    case self::PROPERTY:
                    case self::PROVIDER:
                    case self::PROVIDERS:
                    case self::RANGE:
                    case self::READ:
                    case self::REALLOCATE:
                    case self::REDUCE:
                    case self::RENAME:
                    case self::REL:
                    case self::RELATIONSHIP:
                    case self::RELATIONSHIPS:
                    case self::REMOVE:
                    case self::REPEATABLE:
                    case self::REPLACE:
                    case self::REPORT:
                    case self::REQUIRE:
                    case self::REQUIRED:
                    case self::RESTRICT:
                    case self::RETURN:
                    case self::REVOKE:
                    case self::ROLE:
                    case self::ROLES:
                    case self::ROW:
                    case self::ROWS:
                    case self::SCAN:
                    case self::SEC:
                    case self::SECOND:
                    case self::SECONDARY:
                    case self::SECONDARIES:
                    case self::SECONDS:
                    case self::SEEK:
                    case self::SERVER:
                    case self::SERVERS:
                    case self::SET:
                    case self::SETTING:
                    case self::SETTINGS:
                    case self::SHORTEST_PATH:
                    case self::SHORTEST:
                    case self::SHOW:
                    case self::SIGNED:
                    case self::SINGLE:
                    case self::SKIPROWS:
                    case self::START:
                    case self::STARTS:
                    case self::STATUS:
                    case self::STOP:
                    case self::STRING:
                    case self::SUPPORTED:
                    case self::SUSPENDED:
                    case self::TARGET:
                    case self::TERMINATE:
                    case self::TEXT:
                    case self::THEN:
                    case self::TIME:
                    case self::TIMESTAMP:
                    case self::TIMEZONE:
                    case self::TO:
                    case self::TOPOLOGY:
                    case self::TRAILING:
                    case self::TRANSACTION:
                    case self::TRANSACTIONS:
                    case self::TRAVERSE:
                    case self::TRIM:
                    case self::TRUE:
                    case self::TYPE:
                    case self::TYPES:
                    case self::UNION:
                    case self::UNIQUE:
                    case self::UNIQUENESS:
                    case self::UNWIND:
                    case self::URL:
                    case self::USE:
                    case self::USER:
                    case self::USERS:
                    case self::USING:
                    case self::VALUE:
                    case self::VARCHAR:
                    case self::VECTOR:
                    case self::VERTEX:
                    case self::WAIT:
                    case self::WHEN:
                    case self::WHERE:
                    case self::WITH:
                    case self::WITHOUT:
                    case self::WRITE:
                    case self::XOR:
                    case self::YIELD:
                    case self::ZONE:
                    case self::ZONED:
                    case self::IDENTIFIER:
                        $this->enterOuterAlt($localContext, 1);
                        $this->setState(716);
                        $this->unescapedLabelSymbolicNameString();
                        break;

                    case self::NOT:
                        $this->enterOuterAlt($localContext, 2);
                        $this->setState(717);
                        $this->match(self::NOT);
                        break;

                    case self::NULL:
                        $this->enterOuterAlt($localContext, 3);
                        $this->setState(718);
                        $this->match(self::NULL);
                        break;

                    case self::TYPED:
                        $this->enterOuterAlt($localContext, 4);
                        $this->setState(719);
                        $this->match(self::TYPED);
                        break;

                    case self::NORMALIZED:
                        $this->enterOuterAlt($localContext, 5);
                        $this->setState(720);
                        $this->match(self::NORMALIZED);
                        break;

                    case self::NFC:
                        $this->enterOuterAlt($localContext, 6);
                        $this->setState(721);
                        $this->match(self::NFC);
                        break;

                    case self::NFD:
                        $this->enterOuterAlt($localContext, 7);
                        $this->setState(722);
                        $this->match(self::NFD);
                        break;

                    case self::NFKC:
                        $this->enterOuterAlt($localContext, 8);
                        $this->setState(723);
                        $this->match(self::NFKC);
                        break;

                    case self::NFKD:
                        $this->enterOuterAlt($localContext, 9);
                        $this->setState(724);
                        $this->match(self::NFKD);
                        break;

                    default:
                        throw new NoViableAltException($this);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function symbolicLabelNameString(): Context\SymbolicLabelNameStringContext
        {
            $localContext = new Context\SymbolicLabelNameStringContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 144, self::RULE_symbolicLabelNameString);

            try {
                $this->setState(729);
                $this->errorHandler->sync($this);

                switch ($this->input->LA(1)) {
                    case self::ESCAPED_SYMBOLIC_NAME:
                        $this->enterOuterAlt($localContext, 1);
                        $this->setState(727);
                        $this->escapedSymbolicNameString();
                        break;

                    case self::ACCESS:
                    case self::ACTIVE:
                    case self::ADMIN:
                    case self::ADMINISTRATOR:
                    case self::ALIAS:
                    case self::ALIASES:
                    case self::ALL_SHORTEST_PATHS:
                    case self::ALL:
                    case self::ALTER:
                    case self::AND:
                    case self::ANY:
                    case self::ARRAY:
                    case self::AS:
                    case self::ASC:
                    case self::ASCENDING:
                    case self::ASSIGN:
                    case self::AT:
                    case self::AUTH:
                    case self::BINDINGS:
                    case self::BOOL:
                    case self::BOOLEAN:
                    case self::BOOSTED:
                    case self::BOTH:
                    case self::BREAK:
                    case self::BUILT:
                    case self::BY:
                    case self::CALL:
                    case self::CASCADE:
                    case self::CASE:
                    case self::CHANGE:
                    case self::CIDR:
                    case self::COLLECT:
                    case self::COMMAND:
                    case self::COMMANDS:
                    case self::COMPOSITE:
                    case self::CONCURRENT:
                    case self::CONSTRAINT:
                    case self::CONSTRAINTS:
                    case self::CONTAINS:
                    case self::COPY:
                    case self::CONTINUE:
                    case self::COUNT:
                    case self::CREATE:
                    case self::CSV:
                    case self::CURRENT:
                    case self::DATA:
                    case self::DATABASE:
                    case self::DATABASES:
                    case self::DATE:
                    case self::DATETIME:
                    case self::DBMS:
                    case self::DEALLOCATE:
                    case self::DEFAULT:
                    case self::DEFINED:
                    case self::DELETE:
                    case self::DENY:
                    case self::DESC:
                    case self::DESCENDING:
                    case self::DESTROY:
                    case self::DETACH:
                    case self::DIFFERENT:
                    case self::DISTINCT:
                    case self::DRIVER:
                    case self::DROP:
                    case self::DRYRUN:
                    case self::DUMP:
                    case self::DURATION:
                    case self::EACH:
                    case self::EDGE:
                    case self::ENABLE:
                    case self::ELEMENT:
                    case self::ELEMENTS:
                    case self::ELSE:
                    case self::ENCRYPTED:
                    case self::END:
                    case self::ENDS:
                    case self::EXECUTABLE:
                    case self::EXECUTE:
                    case self::EXIST:
                    case self::EXISTENCE:
                    case self::EXISTS:
                    case self::ERROR:
                    case self::FAIL:
                    case self::FALSE:
                    case self::FIELDTERMINATOR:
                    case self::FINISH:
                    case self::FLOAT:
                    case self::FOR:
                    case self::FOREACH:
                    case self::FROM:
                    case self::FULLTEXT:
                    case self::FUNCTION:
                    case self::FUNCTIONS:
                    case self::GRANT:
                    case self::GRAPH:
                    case self::GRAPHS:
                    case self::GROUP:
                    case self::GROUPS:
                    case self::HEADERS:
                    case self::HOME:
                    case self::ID:
                    case self::IF:
                    case self::IMPERSONATE:
                    case self::IMMUTABLE:
                    case self::IN:
                    case self::INDEX:
                    case self::INDEXES:
                    case self::INF:
                    case self::INFINITY:
                    case self::INSERT:
                    case self::INT:
                    case self::INTEGER:
                    case self::IS:
                    case self::JOIN:
                    case self::KEY:
                    case self::LABEL:
                    case self::LABELS:
                    case self::LEADING:
                    case self::LIMITROWS:
                    case self::LIST:
                    case self::LOAD:
                    case self::LOCAL:
                    case self::LOOKUP:
                    case self::MANAGEMENT:
                    case self::MAP:
                    case self::MATCH:
                    case self::MERGE:
                    case self::NAME:
                    case self::NAMES:
                    case self::NAN:
                    case self::NEW:
                    case self::NODE:
                    case self::NODETACH:
                    case self::NODES:
                    case self::NONE:
                    case self::NORMALIZE:
                    case self::NOTHING:
                    case self::NOWAIT:
                    case self::OF:
                    case self::OFFSET:
                    case self::ON:
                    case self::ONLY:
                    case self::OPTIONAL:
                    case self::OPTIONS:
                    case self::OPTION:
                    case self::OR:
                    case self::ORDER:
                    case self::PASSWORD:
                    case self::PASSWORDS:
                    case self::PATH:
                    case self::PATHS:
                    case self::PLAINTEXT:
                    case self::POINT:
                    case self::POPULATED:
                    case self::PRIMARY:
                    case self::PRIMARIES:
                    case self::PRIVILEGE:
                    case self::PRIVILEGES:
                    case self::PROCEDURE:
                    case self::PROCEDURES:
                    case self::PROPERTIES:
                    case self::PROPERTY:
                    case self::PROVIDER:
                    case self::PROVIDERS:
                    case self::RANGE:
                    case self::READ:
                    case self::REALLOCATE:
                    case self::REDUCE:
                    case self::RENAME:
                    case self::REL:
                    case self::RELATIONSHIP:
                    case self::RELATIONSHIPS:
                    case self::REMOVE:
                    case self::REPEATABLE:
                    case self::REPLACE:
                    case self::REPORT:
                    case self::REQUIRE:
                    case self::REQUIRED:
                    case self::RESTRICT:
                    case self::RETURN:
                    case self::REVOKE:
                    case self::ROLE:
                    case self::ROLES:
                    case self::ROW:
                    case self::ROWS:
                    case self::SCAN:
                    case self::SEC:
                    case self::SECOND:
                    case self::SECONDARY:
                    case self::SECONDARIES:
                    case self::SECONDS:
                    case self::SEEK:
                    case self::SERVER:
                    case self::SERVERS:
                    case self::SET:
                    case self::SETTING:
                    case self::SETTINGS:
                    case self::SHORTEST_PATH:
                    case self::SHORTEST:
                    case self::SHOW:
                    case self::SIGNED:
                    case self::SINGLE:
                    case self::SKIPROWS:
                    case self::START:
                    case self::STARTS:
                    case self::STATUS:
                    case self::STOP:
                    case self::STRING:
                    case self::SUPPORTED:
                    case self::SUSPENDED:
                    case self::TARGET:
                    case self::TERMINATE:
                    case self::TEXT:
                    case self::THEN:
                    case self::TIME:
                    case self::TIMESTAMP:
                    case self::TIMEZONE:
                    case self::TO:
                    case self::TOPOLOGY:
                    case self::TRAILING:
                    case self::TRANSACTION:
                    case self::TRANSACTIONS:
                    case self::TRAVERSE:
                    case self::TRIM:
                    case self::TRUE:
                    case self::TYPE:
                    case self::TYPES:
                    case self::UNION:
                    case self::UNIQUE:
                    case self::UNIQUENESS:
                    case self::UNWIND:
                    case self::URL:
                    case self::USE:
                    case self::USER:
                    case self::USERS:
                    case self::USING:
                    case self::VALUE:
                    case self::VARCHAR:
                    case self::VECTOR:
                    case self::VERTEX:
                    case self::WAIT:
                    case self::WHEN:
                    case self::WHERE:
                    case self::WITH:
                    case self::WITHOUT:
                    case self::WRITE:
                    case self::XOR:
                    case self::YIELD:
                    case self::ZONE:
                    case self::ZONED:
                    case self::IDENTIFIER:
                        $this->enterOuterAlt($localContext, 2);
                        $this->setState(728);
                        $this->unescapedLabelSymbolicNameString();
                        break;

                    default:
                        throw new NoViableAltException($this);
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function unescapedLabelSymbolicNameString(): Context\UnescapedLabelSymbolicNameStringContext
        {
            $localContext = new Context\UnescapedLabelSymbolicNameStringContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 146, self::RULE_unescapedLabelSymbolicNameString);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(731);
                $this->unescapedLabelSymbolicNameString_();
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function unescapedLabelSymbolicNameString_(): Context\UnescapedLabelSymbolicNameString_Context
        {
            $localContext = new Context\UnescapedLabelSymbolicNameString_Context($this->ctx, $this->getState());

            $this->enterRule($localContext, 148, self::RULE_unescapedLabelSymbolicNameString_);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(733);

                $_la = $this->input->LA(1);

                if (!((($_la & ~0x3F) === 0 && ((1 << $_la) & -123145839183872) !== 0) || ((($_la - 64) & ~0x3F) === 0 && ((1 << ($_la - 64)) & -73183498239987713) !== 0) || ((($_la - 128) & ~0x3F) === 0 && ((1 << ($_la - 128)) & -1338045009883137) !== 0) || ((($_la - 192) & ~0x3F) === 0 && ((1 << ($_la - 192)) & -565148994306087) !== 0) || ((($_la - 256) & ~0x3F) === 0 && ((1 << ($_la - 256)) & 140737486257663) !== 0))) {
                    $this->errorHandler->recoverInline($this);
                } else {
                    if (Token::EOF === $this->input->LA(1)) {
                        $this->matchedEOF = true;
                    }

                    $this->errorHandler->reportMatch($this);
                    $this->consume();
                }
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }

        /**
         * @throws RecognitionException
         */
        public function endOfFile(): Context\EndOfFileContext
        {
            $localContext = new Context\EndOfFileContext($this->ctx, $this->getState());

            $this->enterRule($localContext, 150, self::RULE_endOfFile);

            try {
                $this->enterOuterAlt($localContext, 1);
                $this->setState(735);
                $this->match(self::EOF);
            } catch (RecognitionException $exception) {
                $localContext->exception = $exception;
                $this->errorHandler->reportError($this, $exception);
                $this->errorHandler->recover($this, $exception);
            } finally {
                $this->exitRule();
            }

            return $localContext;
        }
    }
}

namespace Context {
    use Antlr\Antlr4\Runtime\ParserRuleContext;
    use Antlr\Antlr4\Runtime\Token;
    use Antlr\Antlr4\Runtime\Tree\ParseTreeListener;
    use Antlr\Antlr4\Runtime\Tree\TerminalNode;
    use CypherPathSubset;
    use CypherPathSubsetListener;

    class StatementsContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_statements;
        }

        public function statement(): ?StatementContext
        {
            return $this->getTypedRuleContext(StatementContext::class, 0);
        }

        public function EOF(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::EOF, 0);
        }

        public function SEMICOLON(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SEMICOLON, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterStatements($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitStatements($this);
            }
        }
    }

    class StatementContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_statement;
        }

        public function regularQuery(): ?RegularQueryContext
        {
            return $this->getTypedRuleContext(RegularQueryContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterStatement($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitStatement($this);
            }
        }
    }

    class RegularQueryContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_regularQuery;
        }

        public function singleQuery(): ?SingleQueryContext
        {
            return $this->getTypedRuleContext(SingleQueryContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterRegularQuery($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitRegularQuery($this);
            }
        }
    }

    class SingleQueryContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_singleQuery;
        }

        public function matchClause(): ?MatchClauseContext
        {
            return $this->getTypedRuleContext(MatchClauseContext::class, 0);
        }

        public function returnClause(): ?ReturnClauseContext
        {
            return $this->getTypedRuleContext(ReturnClauseContext::class, 0);
        }

        public function unwindClause(): ?UnwindClauseContext
        {
            return $this->getTypedRuleContext(UnwindClauseContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterSingleQuery($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitSingleQuery($this);
            }
        }
    }

    class ReturnClauseContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_returnClause;
        }

        public function RETURN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::RETURN, 0);
        }

        public function returnBody(): ?ReturnBodyContext
        {
            return $this->getTypedRuleContext(ReturnBodyContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterReturnClause($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitReturnClause($this);
            }
        }
    }

    class ReturnBodyContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_returnBody;
        }

        public function returnItems(): ?ReturnItemsContext
        {
            return $this->getTypedRuleContext(ReturnItemsContext::class, 0);
        }

        public function DISTINCT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DISTINCT, 0);
        }

        public function orderBy(): ?OrderByContext
        {
            return $this->getTypedRuleContext(OrderByContext::class, 0);
        }

        public function skip(): ?SkipContext
        {
            return $this->getTypedRuleContext(SkipContext::class, 0);
        }

        public function limit(): ?LimitContext
        {
            return $this->getTypedRuleContext(LimitContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterReturnBody($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitReturnBody($this);
            }
        }
    }

    class ReturnItemContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_returnItem;
        }

        public function variable(): ?VariableContext
        {
            return $this->getTypedRuleContext(VariableContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterReturnItem($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitReturnItem($this);
            }
        }
    }

    class ReturnItemsContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_returnItems;
        }

        public function returnItem(): ?ReturnItemContext
        {
            return $this->getTypedRuleContext(ReturnItemContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterReturnItems($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitReturnItems($this);
            }
        }
    }

    class OrderItemContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_orderItem;
        }

        public function expression(): ?ExpressionContext
        {
            return $this->getTypedRuleContext(ExpressionContext::class, 0);
        }

        public function ascToken(): ?AscTokenContext
        {
            return $this->getTypedRuleContext(AscTokenContext::class, 0);
        }

        public function descToken(): ?DescTokenContext
        {
            return $this->getTypedRuleContext(DescTokenContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterOrderItem($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitOrderItem($this);
            }
        }
    }

    class AscTokenContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_ascToken;
        }

        public function ASC(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ASC, 0);
        }

        public function ASCENDING(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ASCENDING, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterAscToken($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitAscToken($this);
            }
        }
    }

    class DescTokenContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_descToken;
        }

        public function DESC(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DESC, 0);
        }

        public function DESCENDING(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DESCENDING, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterDescToken($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitDescToken($this);
            }
        }
    }

    class OrderByContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_orderBy;
        }

        public function ORDER(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ORDER, 0);
        }

        public function BY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::BY, 0);
        }

        /**
         * @return array<OrderItemContext>|OrderItemContext|null
         */
        public function orderItem(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(OrderItemContext::class);
            }

            return $this->getTypedRuleContext(OrderItemContext::class, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function COMMA(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::COMMA);
            }

            return $this->getToken(CypherPathSubset::COMMA, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterOrderBy($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitOrderBy($this);
            }
        }
    }

    class SkipContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_skip;
        }

        public function expression(): ?ExpressionContext
        {
            return $this->getTypedRuleContext(ExpressionContext::class, 0);
        }

        public function OFFSET(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::OFFSET, 0);
        }

        public function SKIPROWS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SKIPROWS, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterSkip($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitSkip($this);
            }
        }
    }

    class LimitContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_limit;
        }

        public function LIMITROWS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LIMITROWS, 0);
        }

        public function expression(): ?ExpressionContext
        {
            return $this->getTypedRuleContext(ExpressionContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterLimit($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitLimit($this);
            }
        }
    }

    class WhereClauseContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_whereClause;
        }

        public function WHERE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::WHERE, 0);
        }

        public function expression(): ?ExpressionContext
        {
            return $this->getTypedRuleContext(ExpressionContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterWhereClause($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitWhereClause($this);
            }
        }
    }

    class MatchClauseContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_matchClause;
        }

        public function MATCH(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::MATCH, 0);
        }

        public function patternList(): ?PatternListContext
        {
            return $this->getTypedRuleContext(PatternListContext::class, 0);
        }

        public function whereClause(): ?WhereClauseContext
        {
            return $this->getTypedRuleContext(WhereClauseContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterMatchClause($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitMatchClause($this);
            }
        }
    }

    class UnwindClauseContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_unwindClause;
        }

        public function UNWIND(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::UNWIND, 0);
        }

        public function expression(): ?ExpressionContext
        {
            return $this->getTypedRuleContext(ExpressionContext::class, 0);
        }

        public function AS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::AS, 0);
        }

        public function variable(): ?VariableContext
        {
            return $this->getTypedRuleContext(VariableContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterUnwindClause($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitUnwindClause($this);
            }
        }
    }

    class PatternListContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_patternList;
        }

        public function pattern(): ?PatternContext
        {
            return $this->getTypedRuleContext(PatternContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterPatternList($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitPatternList($this);
            }
        }
    }

    class PatternContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_pattern;
        }

        public function anonymousPattern(): ?AnonymousPatternContext
        {
            return $this->getTypedRuleContext(AnonymousPatternContext::class, 0);
        }

        public function variable(): ?VariableContext
        {
            return $this->getTypedRuleContext(VariableContext::class, 0);
        }

        public function EQ(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::EQ, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterPattern($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitPattern($this);
            }
        }
    }

    class QuantifierContext extends ParserRuleContext
    {
        /**
         * @var Token|null
         */
        public $from;

        /**
         * @var Token|null
         */
        public $to;

        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_quantifier;
        }

        public function LCURLY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LCURLY, 0);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function UNSIGNED_DECIMAL_INTEGER(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::UNSIGNED_DECIMAL_INTEGER);
            }

            return $this->getToken(CypherPathSubset::UNSIGNED_DECIMAL_INTEGER, $index);
        }

        public function RCURLY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::RCURLY, 0);
        }

        public function COMMA(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::COMMA, 0);
        }

        public function PLUS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PLUS, 0);
        }

        public function TIMES(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TIMES, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterQuantifier($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitQuantifier($this);
            }
        }
    }

    class AnonymousPatternContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_anonymousPattern;
        }

        public function patternElement(): ?PatternElementContext
        {
            return $this->getTypedRuleContext(PatternElementContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterAnonymousPattern($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitAnonymousPattern($this);
            }
        }
    }

    class PatternElementContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_patternElement;
        }

        /**
         * @return array<NodePatternContext>|NodePatternContext|null
         */
        public function nodePattern(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(NodePatternContext::class);
            }

            return $this->getTypedRuleContext(NodePatternContext::class, $index);
        }

        /**
         * @return array<ParenthesizedPathContext>|ParenthesizedPathContext|null
         */
        public function parenthesizedPath(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(ParenthesizedPathContext::class);
            }

            return $this->getTypedRuleContext(ParenthesizedPathContext::class, $index);
        }

        /**
         * @return array<RelationshipPatternContext>|RelationshipPatternContext|null
         */
        public function relationshipPattern(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(RelationshipPatternContext::class);
            }

            return $this->getTypedRuleContext(RelationshipPatternContext::class, $index);
        }

        /**
         * @return array<QuantifierContext>|QuantifierContext|null
         */
        public function quantifier(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(QuantifierContext::class);
            }

            return $this->getTypedRuleContext(QuantifierContext::class, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterPatternElement($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitPatternElement($this);
            }
        }
    }

    class NodePatternContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_nodePattern;
        }

        public function LPAREN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LPAREN, 0);
        }

        public function RPAREN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::RPAREN, 0);
        }

        public function variable(): ?VariableContext
        {
            return $this->getTypedRuleContext(VariableContext::class, 0);
        }

        public function labelExpression(): ?LabelExpressionContext
        {
            return $this->getTypedRuleContext(LabelExpressionContext::class, 0);
        }

        public function properties(): ?PropertiesContext
        {
            return $this->getTypedRuleContext(PropertiesContext::class, 0);
        }

        public function WHERE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::WHERE, 0);
        }

        public function expression(): ?ExpressionContext
        {
            return $this->getTypedRuleContext(ExpressionContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterNodePattern($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitNodePattern($this);
            }
        }
    }

    class ParenthesizedPathContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_parenthesizedPath;
        }

        public function LPAREN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LPAREN, 0);
        }

        public function pattern(): ?PatternContext
        {
            return $this->getTypedRuleContext(PatternContext::class, 0);
        }

        public function RPAREN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::RPAREN, 0);
        }

        public function WHERE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::WHERE, 0);
        }

        public function expression(): ?ExpressionContext
        {
            return $this->getTypedRuleContext(ExpressionContext::class, 0);
        }

        public function quantifier(): ?QuantifierContext
        {
            return $this->getTypedRuleContext(QuantifierContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterParenthesizedPath($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitParenthesizedPath($this);
            }
        }
    }

    class PropertiesContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_properties;
        }

        public function map(): ?MapContext
        {
            return $this->getTypedRuleContext(MapContext::class, 0);
        }

        public function parameter(): ?ParameterContext
        {
            return $this->getTypedRuleContext(ParameterContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterProperties($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitProperties($this);
            }
        }
    }

    class RelationshipPatternContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_relationshipPattern;
        }

        /**
         * @return array<ArrowLineContext>|ArrowLineContext|null
         */
        public function arrowLine(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(ArrowLineContext::class);
            }

            return $this->getTypedRuleContext(ArrowLineContext::class, $index);
        }

        public function leftArrow(): ?LeftArrowContext
        {
            return $this->getTypedRuleContext(LeftArrowContext::class, 0);
        }

        public function LBRACKET(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LBRACKET, 0);
        }

        public function RBRACKET(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::RBRACKET, 0);
        }

        public function rightArrow(): ?RightArrowContext
        {
            return $this->getTypedRuleContext(RightArrowContext::class, 0);
        }

        public function variable(): ?VariableContext
        {
            return $this->getTypedRuleContext(VariableContext::class, 0);
        }

        public function labelExpression(): ?LabelExpressionContext
        {
            return $this->getTypedRuleContext(LabelExpressionContext::class, 0);
        }

        public function pathLength(): ?PathLengthContext
        {
            return $this->getTypedRuleContext(PathLengthContext::class, 0);
        }

        public function properties(): ?PropertiesContext
        {
            return $this->getTypedRuleContext(PropertiesContext::class, 0);
        }

        public function WHERE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::WHERE, 0);
        }

        public function expression(): ?ExpressionContext
        {
            return $this->getTypedRuleContext(ExpressionContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterRelationshipPattern($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitRelationshipPattern($this);
            }
        }
    }

    class LeftArrowContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_leftArrow;
        }

        public function LT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LT, 0);
        }

        public function ARROW_LEFT_HEAD(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ARROW_LEFT_HEAD, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterLeftArrow($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitLeftArrow($this);
            }
        }
    }

    class ArrowLineContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_arrowLine;
        }

        public function ARROW_LINE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ARROW_LINE, 0);
        }

        public function MINUS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::MINUS, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterArrowLine($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitArrowLine($this);
            }
        }
    }

    class RightArrowContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_rightArrow;
        }

        public function GT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::GT, 0);
        }

        public function ARROW_RIGHT_HEAD(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ARROW_RIGHT_HEAD, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterRightArrow($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitRightArrow($this);
            }
        }
    }

    class PathLengthContext extends ParserRuleContext
    {
        /**
         * @var Token|null
         */
        public $from;

        /**
         * @var Token|null
         */
        public $to;

        /**
         * @var Token|null
         */
        public $single;

        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_pathLength;
        }

        public function TIMES(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TIMES, 0);
        }

        public function DOTDOT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DOTDOT, 0);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function UNSIGNED_DECIMAL_INTEGER(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::UNSIGNED_DECIMAL_INTEGER);
            }

            return $this->getToken(CypherPathSubset::UNSIGNED_DECIMAL_INTEGER, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterPathLength($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitPathLength($this);
            }
        }
    }

    class LabelExpressionContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_labelExpression;
        }

        public function COLON(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::COLON, 0);
        }

        public function labelExpression4(): ?LabelExpression4Context
        {
            return $this->getTypedRuleContext(LabelExpression4Context::class, 0);
        }

        public function IS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::IS, 0);
        }

        public function labelExpression4Is(): ?LabelExpression4IsContext
        {
            return $this->getTypedRuleContext(LabelExpression4IsContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterLabelExpression($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitLabelExpression($this);
            }
        }
    }

    class LabelExpression4Context extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_labelExpression4;
        }

        /**
         * @return array<LabelExpression3Context>|LabelExpression3Context|null
         */
        public function labelExpression3(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(LabelExpression3Context::class);
            }

            return $this->getTypedRuleContext(LabelExpression3Context::class, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function BAR(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::BAR);
            }

            return $this->getToken(CypherPathSubset::BAR, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function COLON(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::COLON);
            }

            return $this->getToken(CypherPathSubset::COLON, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterLabelExpression4($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitLabelExpression4($this);
            }
        }
    }

    class LabelExpression4IsContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_labelExpression4Is;
        }

        /**
         * @return array<LabelExpression3IsContext>|LabelExpression3IsContext|null
         */
        public function labelExpression3Is(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(LabelExpression3IsContext::class);
            }

            return $this->getTypedRuleContext(LabelExpression3IsContext::class, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function BAR(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::BAR);
            }

            return $this->getToken(CypherPathSubset::BAR, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function COLON(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::COLON);
            }

            return $this->getToken(CypherPathSubset::COLON, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterLabelExpression4Is($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitLabelExpression4Is($this);
            }
        }
    }

    class LabelExpression3Context extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_labelExpression3;
        }

        /**
         * @return array<LabelExpression2Context>|LabelExpression2Context|null
         */
        public function labelExpression2(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(LabelExpression2Context::class);
            }

            return $this->getTypedRuleContext(LabelExpression2Context::class, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function AMPERSAND(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::AMPERSAND);
            }

            return $this->getToken(CypherPathSubset::AMPERSAND, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function COLON(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::COLON);
            }

            return $this->getToken(CypherPathSubset::COLON, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterLabelExpression3($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitLabelExpression3($this);
            }
        }
    }

    class LabelExpression3IsContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_labelExpression3Is;
        }

        /**
         * @return array<LabelExpression2IsContext>|LabelExpression2IsContext|null
         */
        public function labelExpression2Is(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(LabelExpression2IsContext::class);
            }

            return $this->getTypedRuleContext(LabelExpression2IsContext::class, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function AMPERSAND(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::AMPERSAND);
            }

            return $this->getToken(CypherPathSubset::AMPERSAND, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function COLON(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::COLON);
            }

            return $this->getToken(CypherPathSubset::COLON, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterLabelExpression3Is($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitLabelExpression3Is($this);
            }
        }
    }

    class LabelExpression2Context extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_labelExpression2;
        }

        public function labelExpression1(): ?LabelExpression1Context
        {
            return $this->getTypedRuleContext(LabelExpression1Context::class, 0);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function EXCLAMATION_MARK(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::EXCLAMATION_MARK);
            }

            return $this->getToken(CypherPathSubset::EXCLAMATION_MARK, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterLabelExpression2($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitLabelExpression2($this);
            }
        }
    }

    class LabelExpression2IsContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_labelExpression2Is;
        }

        public function labelExpression1Is(): ?LabelExpression1IsContext
        {
            return $this->getTypedRuleContext(LabelExpression1IsContext::class, 0);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function EXCLAMATION_MARK(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::EXCLAMATION_MARK);
            }

            return $this->getToken(CypherPathSubset::EXCLAMATION_MARK, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterLabelExpression2Is($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitLabelExpression2Is($this);
            }
        }
    }

    class LabelExpression1Context extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_labelExpression1;
        }

        public function copyFrom(ParserRuleContext $context): void
        {
            parent::copyFrom($context);
        }
    }

    class AnyLabelContext extends LabelExpression1Context
    {
        public function __construct(LabelExpression1Context $context)
        {
            parent::__construct($context);

            $this->copyFrom($context);
        }

        public function PERCENT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PERCENT, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterAnyLabel($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitAnyLabel($this);
            }
        }
    }

    class LabelNameContext extends LabelExpression1Context
    {
        public function __construct(LabelExpression1Context $context)
        {
            parent::__construct($context);

            $this->copyFrom($context);
        }

        public function symbolicNameString(): ?SymbolicNameStringContext
        {
            return $this->getTypedRuleContext(SymbolicNameStringContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterLabelName($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitLabelName($this);
            }
        }
    }

    class ParenthesizedLabelExpressionContext extends LabelExpression1Context
    {
        public function __construct(LabelExpression1Context $context)
        {
            parent::__construct($context);

            $this->copyFrom($context);
        }

        public function LPAREN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LPAREN, 0);
        }

        public function labelExpression4(): ?LabelExpression4Context
        {
            return $this->getTypedRuleContext(LabelExpression4Context::class, 0);
        }

        public function RPAREN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::RPAREN, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterParenthesizedLabelExpression($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitParenthesizedLabelExpression($this);
            }
        }
    }

    class LabelExpression1IsContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_labelExpression1Is;
        }

        public function copyFrom(ParserRuleContext $context): void
        {
            parent::copyFrom($context);
        }
    }

    class ParenthesizedLabelExpressionIsContext extends LabelExpression1IsContext
    {
        public function __construct(LabelExpression1IsContext $context)
        {
            parent::__construct($context);

            $this->copyFrom($context);
        }

        public function LPAREN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LPAREN, 0);
        }

        public function labelExpression4Is(): ?LabelExpression4IsContext
        {
            return $this->getTypedRuleContext(LabelExpression4IsContext::class, 0);
        }

        public function RPAREN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::RPAREN, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterParenthesizedLabelExpressionIs($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitParenthesizedLabelExpressionIs($this);
            }
        }
    }

    class AnyLabelIsContext extends LabelExpression1IsContext
    {
        public function __construct(LabelExpression1IsContext $context)
        {
            parent::__construct($context);

            $this->copyFrom($context);
        }

        public function PERCENT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PERCENT, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterAnyLabelIs($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitAnyLabelIs($this);
            }
        }
    }

    class LabelNameIsContext extends LabelExpression1IsContext
    {
        public function __construct(LabelExpression1IsContext $context)
        {
            parent::__construct($context);

            $this->copyFrom($context);
        }

        public function symbolicLabelNameString(): ?SymbolicLabelNameStringContext
        {
            return $this->getTypedRuleContext(SymbolicLabelNameStringContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterLabelNameIs($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitLabelNameIs($this);
            }
        }
    }

    class ExpressionContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_expression;
        }

        /**
         * @return array<Expression11Context>|Expression11Context|null
         */
        public function expression11(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(Expression11Context::class);
            }

            return $this->getTypedRuleContext(Expression11Context::class, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function OR(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::OR);
            }

            return $this->getToken(CypherPathSubset::OR, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterExpression($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitExpression($this);
            }
        }
    }

    class Expression11Context extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_expression11;
        }

        /**
         * @return array<Expression10Context>|Expression10Context|null
         */
        public function expression10(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(Expression10Context::class);
            }

            return $this->getTypedRuleContext(Expression10Context::class, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function XOR(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::XOR);
            }

            return $this->getToken(CypherPathSubset::XOR, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterExpression11($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitExpression11($this);
            }
        }
    }

    class Expression10Context extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_expression10;
        }

        /**
         * @return array<Expression9Context>|Expression9Context|null
         */
        public function expression9(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(Expression9Context::class);
            }

            return $this->getTypedRuleContext(Expression9Context::class, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function AND(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::AND);
            }

            return $this->getToken(CypherPathSubset::AND, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterExpression10($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitExpression10($this);
            }
        }
    }

    class Expression9Context extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_expression9;
        }

        public function expression8(): ?Expression8Context
        {
            return $this->getTypedRuleContext(Expression8Context::class, 0);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function NOT(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::NOT);
            }

            return $this->getToken(CypherPathSubset::NOT, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterExpression9($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitExpression9($this);
            }
        }
    }

    class Expression8Context extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_expression8;
        }

        /**
         * @return array<Expression7Context>|Expression7Context|null
         */
        public function expression7(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(Expression7Context::class);
            }

            return $this->getTypedRuleContext(Expression7Context::class, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function EQ(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::EQ);
            }

            return $this->getToken(CypherPathSubset::EQ, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function INVALID_NEQ(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::INVALID_NEQ);
            }

            return $this->getToken(CypherPathSubset::INVALID_NEQ, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function NEQ(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::NEQ);
            }

            return $this->getToken(CypherPathSubset::NEQ, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function LE(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::LE);
            }

            return $this->getToken(CypherPathSubset::LE, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function GE(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::GE);
            }

            return $this->getToken(CypherPathSubset::GE, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function LT(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::LT);
            }

            return $this->getToken(CypherPathSubset::LT, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function GT(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::GT);
            }

            return $this->getToken(CypherPathSubset::GT, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterExpression8($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitExpression8($this);
            }
        }
    }

    class Expression7Context extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_expression7;
        }

        public function expression6(): ?Expression6Context
        {
            return $this->getTypedRuleContext(Expression6Context::class, 0);
        }

        public function comparisonExpression6(): ?ComparisonExpression6Context
        {
            return $this->getTypedRuleContext(ComparisonExpression6Context::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterExpression7($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitExpression7($this);
            }
        }
    }

    class ComparisonExpression6Context extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_comparisonExpression6;
        }

        public function copyFrom(ParserRuleContext $context): void
        {
            parent::copyFrom($context);
        }
    }

    class TypeComparisonContext extends ComparisonExpression6Context
    {
        public function __construct(ComparisonExpression6Context $context)
        {
            parent::__construct($context);

            $this->copyFrom($context);
        }

        public function type(): ?TypeContext
        {
            return $this->getTypedRuleContext(TypeContext::class, 0);
        }

        public function IS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::IS, 0);
        }

        public function COLONCOLON(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::COLONCOLON, 0);
        }

        public function TYPED(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TYPED, 0);
        }

        public function NOT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NOT, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterTypeComparison($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitTypeComparison($this);
            }
        }
    }

    class StringAndListComparisonContext extends ComparisonExpression6Context
    {
        public function __construct(ComparisonExpression6Context $context)
        {
            parent::__construct($context);

            $this->copyFrom($context);
        }

        public function expression6(): ?Expression6Context
        {
            return $this->getTypedRuleContext(Expression6Context::class, 0);
        }

        public function REGEQ(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::REGEQ, 0);
        }

        public function STARTS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::STARTS, 0);
        }

        public function WITH(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::WITH, 0);
        }

        public function ENDS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ENDS, 0);
        }

        public function CONTAINS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::CONTAINS, 0);
        }

        public function IN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::IN, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterStringAndListComparison($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitStringAndListComparison($this);
            }
        }
    }

    class NormalFormComparisonContext extends ComparisonExpression6Context
    {
        public function __construct(ComparisonExpression6Context $context)
        {
            parent::__construct($context);

            $this->copyFrom($context);
        }

        public function IS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::IS, 0);
        }

        public function NORMALIZED(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NORMALIZED, 0);
        }

        public function NOT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NOT, 0);
        }

        public function normalForm(): ?NormalFormContext
        {
            return $this->getTypedRuleContext(NormalFormContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterNormalFormComparison($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitNormalFormComparison($this);
            }
        }
    }

    class NullComparisonContext extends ComparisonExpression6Context
    {
        public function __construct(ComparisonExpression6Context $context)
        {
            parent::__construct($context);

            $this->copyFrom($context);
        }

        public function IS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::IS, 0);
        }

        public function null(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NULL, 0);
        }

        public function NOT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NOT, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterNullComparison($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitNullComparison($this);
            }
        }
    }

    class NormalFormContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_normalForm;
        }

        public function NFC(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NFC, 0);
        }

        public function NFD(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NFD, 0);
        }

        public function NFKC(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NFKC, 0);
        }

        public function NFKD(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NFKD, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterNormalForm($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitNormalForm($this);
            }
        }
    }

    class Expression6Context extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_expression6;
        }

        /**
         * @return array<Expression5Context>|Expression5Context|null
         */
        public function expression5(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(Expression5Context::class);
            }

            return $this->getTypedRuleContext(Expression5Context::class, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function PLUS(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::PLUS);
            }

            return $this->getToken(CypherPathSubset::PLUS, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function MINUS(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::MINUS);
            }

            return $this->getToken(CypherPathSubset::MINUS, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function DOUBLEBAR(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::DOUBLEBAR);
            }

            return $this->getToken(CypherPathSubset::DOUBLEBAR, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterExpression6($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitExpression6($this);
            }
        }
    }

    class Expression5Context extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_expression5;
        }

        /**
         * @return array<Expression4Context>|Expression4Context|null
         */
        public function expression4(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(Expression4Context::class);
            }

            return $this->getTypedRuleContext(Expression4Context::class, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function TIMES(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::TIMES);
            }

            return $this->getToken(CypherPathSubset::TIMES, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function DIVIDE(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::DIVIDE);
            }

            return $this->getToken(CypherPathSubset::DIVIDE, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function PERCENT(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::PERCENT);
            }

            return $this->getToken(CypherPathSubset::PERCENT, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterExpression5($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitExpression5($this);
            }
        }
    }

    class Expression4Context extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_expression4;
        }

        /**
         * @return array<Expression3Context>|Expression3Context|null
         */
        public function expression3(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(Expression3Context::class);
            }

            return $this->getTypedRuleContext(Expression3Context::class, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function POW(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::POW);
            }

            return $this->getToken(CypherPathSubset::POW, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterExpression4($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitExpression4($this);
            }
        }
    }

    class Expression3Context extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_expression3;
        }

        public function expression2(): ?Expression2Context
        {
            return $this->getTypedRuleContext(Expression2Context::class, 0);
        }

        public function PLUS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PLUS, 0);
        }

        public function MINUS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::MINUS, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterExpression3($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitExpression3($this);
            }
        }
    }

    class Expression2Context extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_expression2;
        }

        public function expression1(): ?Expression1Context
        {
            return $this->getTypedRuleContext(Expression1Context::class, 0);
        }

        /**
         * @return array<PostFixContext>|PostFixContext|null
         */
        public function postFix(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(PostFixContext::class);
            }

            return $this->getTypedRuleContext(PostFixContext::class, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterExpression2($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitExpression2($this);
            }
        }
    }

    class PostFixContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_postFix;
        }

        public function copyFrom(ParserRuleContext $context): void
        {
            parent::copyFrom($context);
        }
    }

    class IndexPostfixContext extends PostFixContext
    {
        public function __construct(PostFixContext $context)
        {
            parent::__construct($context);

            $this->copyFrom($context);
        }

        public function LBRACKET(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LBRACKET, 0);
        }

        public function expression(): ?ExpressionContext
        {
            return $this->getTypedRuleContext(ExpressionContext::class, 0);
        }

        public function RBRACKET(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::RBRACKET, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterIndexPostfix($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitIndexPostfix($this);
            }
        }
    }

    class PropertyPostfixContext extends PostFixContext
    {
        public function __construct(PostFixContext $context)
        {
            parent::__construct($context);

            $this->copyFrom($context);
        }

        public function property(): ?PropertyContext
        {
            return $this->getTypedRuleContext(PropertyContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterPropertyPostfix($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitPropertyPostfix($this);
            }
        }
    }

    class LabelPostfixContext extends PostFixContext
    {
        public function __construct(PostFixContext $context)
        {
            parent::__construct($context);

            $this->copyFrom($context);
        }

        public function labelExpression(): ?LabelExpressionContext
        {
            return $this->getTypedRuleContext(LabelExpressionContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterLabelPostfix($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitLabelPostfix($this);
            }
        }
    }

    class RangePostfixContext extends PostFixContext
    {
        /**
         * @var ExpressionContext|null
         */
        public $fromExp;

        /**
         * @var ExpressionContext|null
         */
        public $toExp;

        public function __construct(PostFixContext $context)
        {
            parent::__construct($context);

            $this->copyFrom($context);
        }

        public function LBRACKET(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LBRACKET, 0);
        }

        public function DOTDOT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DOTDOT, 0);
        }

        public function RBRACKET(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::RBRACKET, 0);
        }

        /**
         * @return array<ExpressionContext>|ExpressionContext|null
         */
        public function expression(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(ExpressionContext::class);
            }

            return $this->getTypedRuleContext(ExpressionContext::class, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterRangePostfix($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitRangePostfix($this);
            }
        }
    }

    class PropertyContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_property;
        }

        public function DOT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DOT, 0);
        }

        public function propertyKeyName(): ?PropertyKeyNameContext
        {
            return $this->getTypedRuleContext(PropertyKeyNameContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterProperty($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitProperty($this);
            }
        }
    }

    class Expression1Context extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_expression1;
        }

        public function literal(): ?LiteralContext
        {
            return $this->getTypedRuleContext(LiteralContext::class, 0);
        }

        public function parameter(): ?ParameterContext
        {
            return $this->getTypedRuleContext(ParameterContext::class, 0);
        }

        public function parenthesizedExpression(): ?ParenthesizedExpressionContext
        {
            return $this->getTypedRuleContext(ParenthesizedExpressionContext::class, 0);
        }

        public function variable(): ?VariableContext
        {
            return $this->getTypedRuleContext(VariableContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterExpression1($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitExpression1($this);
            }
        }
    }

    class LiteralContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_literal;
        }

        public function copyFrom(ParserRuleContext $context): void
        {
            parent::copyFrom($context);
        }
    }

    class NummericLiteralContext extends LiteralContext
    {
        public function __construct(LiteralContext $context)
        {
            parent::__construct($context);

            $this->copyFrom($context);
        }

        public function numberLiteral(): ?NumberLiteralContext
        {
            return $this->getTypedRuleContext(NumberLiteralContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterNummericLiteral($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitNummericLiteral($this);
            }
        }
    }

    class BooleanLiteralContext extends LiteralContext
    {
        public function __construct(LiteralContext $context)
        {
            parent::__construct($context);

            $this->copyFrom($context);
        }

        public function true(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TRUE, 0);
        }

        public function false(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::FALSE, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterBooleanLiteral($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitBooleanLiteral($this);
            }
        }
    }

    class KeywordLiteralContext extends LiteralContext
    {
        public function __construct(LiteralContext $context)
        {
            parent::__construct($context);

            $this->copyFrom($context);
        }

        public function INF(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::INF, 0);
        }

        public function INFINITY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::INFINITY, 0);
        }

        public function NAN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NAN, 0);
        }

        public function null(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NULL, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterKeywordLiteral($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitKeywordLiteral($this);
            }
        }
    }

    class OtherLiteralContext extends LiteralContext
    {
        public function __construct(LiteralContext $context)
        {
            parent::__construct($context);

            $this->copyFrom($context);
        }

        public function map(): ?MapContext
        {
            return $this->getTypedRuleContext(MapContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterOtherLiteral($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitOtherLiteral($this);
            }
        }
    }

    class StringsLiteralContext extends LiteralContext
    {
        public function __construct(LiteralContext $context)
        {
            parent::__construct($context);

            $this->copyFrom($context);
        }

        public function stringLiteral(): ?StringLiteralContext
        {
            return $this->getTypedRuleContext(StringLiteralContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterStringsLiteral($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitStringsLiteral($this);
            }
        }
    }

    class ParenthesizedExpressionContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_parenthesizedExpression;
        }

        public function LPAREN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LPAREN, 0);
        }

        public function expression(): ?ExpressionContext
        {
            return $this->getTypedRuleContext(ExpressionContext::class, 0);
        }

        public function RPAREN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::RPAREN, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterParenthesizedExpression($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitParenthesizedExpression($this);
            }
        }
    }

    class NumberLiteralContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_numberLiteral;
        }

        public function DECIMAL_DOUBLE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DECIMAL_DOUBLE, 0);
        }

        public function UNSIGNED_DECIMAL_INTEGER(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::UNSIGNED_DECIMAL_INTEGER, 0);
        }

        public function UNSIGNED_HEX_INTEGER(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::UNSIGNED_HEX_INTEGER, 0);
        }

        public function UNSIGNED_OCTAL_INTEGER(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::UNSIGNED_OCTAL_INTEGER, 0);
        }

        public function MINUS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::MINUS, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterNumberLiteral($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitNumberLiteral($this);
            }
        }
    }

    class PropertyKeyNameContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_propertyKeyName;
        }

        public function symbolicNameString(): ?SymbolicNameStringContext
        {
            return $this->getTypedRuleContext(SymbolicNameStringContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterPropertyKeyName($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitPropertyKeyName($this);
            }
        }
    }

    class ParameterContext extends ParserRuleContext
    {
        /**
         * @var string|null
         */
        public $paramType;

        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null, ?string $paramType = null)
        {
            parent::__construct($parent, $invokingState);

            $this->paramType = $paramType ?? $this->paramType;
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_parameter;
        }

        public function DOLLAR(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DOLLAR, 0);
        }

        public function parameterName(): ?ParameterNameContext
        {
            return $this->getTypedRuleContext(ParameterNameContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterParameter($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitParameter($this);
            }
        }
    }

    class ParameterNameContext extends ParserRuleContext
    {
        /**
         * @var string|null
         */
        public $paramType;

        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null, ?string $paramType = null)
        {
            parent::__construct($parent, $invokingState);

            $this->paramType = $paramType ?? $this->paramType;
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_parameterName;
        }

        public function symbolicNameString(): ?SymbolicNameStringContext
        {
            return $this->getTypedRuleContext(SymbolicNameStringContext::class, 0);
        }

        public function UNSIGNED_DECIMAL_INTEGER(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::UNSIGNED_DECIMAL_INTEGER, 0);
        }

        public function UNSIGNED_OCTAL_INTEGER(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::UNSIGNED_OCTAL_INTEGER, 0);
        }

        public function EXTENDED_IDENTIFIER(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::EXTENDED_IDENTIFIER, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterParameterName($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitParameterName($this);
            }
        }
    }

    class VariableContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_variable;
        }

        public function symbolicNameString(): ?SymbolicNameStringContext
        {
            return $this->getTypedRuleContext(SymbolicNameStringContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterVariable($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitVariable($this);
            }
        }
    }

    class TypeContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_type;
        }

        /**
         * @return array<TypePartContext>|TypePartContext|null
         */
        public function typePart(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(TypePartContext::class);
            }

            return $this->getTypedRuleContext(TypePartContext::class, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function BAR(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::BAR);
            }

            return $this->getToken(CypherPathSubset::BAR, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterType($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitType($this);
            }
        }
    }

    class TypePartContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_typePart;
        }

        public function typeName(): ?TypeNameContext
        {
            return $this->getTypedRuleContext(TypeNameContext::class, 0);
        }

        public function typeNullability(): ?TypeNullabilityContext
        {
            return $this->getTypedRuleContext(TypeNullabilityContext::class, 0);
        }

        /**
         * @return array<TypeListSuffixContext>|TypeListSuffixContext|null
         */
        public function typeListSuffix(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(TypeListSuffixContext::class);
            }

            return $this->getTypedRuleContext(TypeListSuffixContext::class, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterTypePart($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitTypePart($this);
            }
        }
    }

    class TypeNameContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_typeName;
        }

        public function NOTHING(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NOTHING, 0);
        }

        public function null(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NULL, 0);
        }

        public function BOOL(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::BOOL, 0);
        }

        public function BOOLEAN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::BOOLEAN, 0);
        }

        public function VARCHAR(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::VARCHAR, 0);
        }

        public function STRING(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::STRING, 0);
        }

        public function INT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::INT, 0);
        }

        public function INTEGER(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::INTEGER, 0);
        }

        public function SIGNED(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SIGNED, 0);
        }

        public function FLOAT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::FLOAT, 0);
        }

        public function DATE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DATE, 0);
        }

        public function LOCAL(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LOCAL, 0);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function TIME(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::TIME);
            }

            return $this->getToken(CypherPathSubset::TIME, $index);
        }

        public function DATETIME(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DATETIME, 0);
        }

        public function ZONED(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ZONED, 0);
        }

        public function WITHOUT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::WITHOUT, 0);
        }

        public function WITH(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::WITH, 0);
        }

        public function TIMEZONE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TIMEZONE, 0);
        }

        public function ZONE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ZONE, 0);
        }

        public function TIMESTAMP(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TIMESTAMP, 0);
        }

        public function DURATION(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DURATION, 0);
        }

        public function POINT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::POINT, 0);
        }

        public function NODE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NODE, 0);
        }

        public function VERTEX(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::VERTEX, 0);
        }

        public function RELATIONSHIP(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::RELATIONSHIP, 0);
        }

        public function EDGE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::EDGE, 0);
        }

        public function MAP(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::MAP, 0);
        }

        public function LT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LT, 0);
        }

        public function type(): ?TypeContext
        {
            return $this->getTypedRuleContext(TypeContext::class, 0);
        }

        public function GT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::GT, 0);
        }

        public function LIST(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LIST, 0);
        }

        public function ARRAY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ARRAY, 0);
        }

        public function PATH(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PATH, 0);
        }

        public function PATHS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PATHS, 0);
        }

        public function PROPERTY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PROPERTY, 0);
        }

        public function VALUE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::VALUE, 0);
        }

        public function ANY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ANY, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterTypeName($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitTypeName($this);
            }
        }
    }

    class TypeNullabilityContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_typeNullability;
        }

        public function NOT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NOT, 0);
        }

        public function null(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NULL, 0);
        }

        public function EXCLAMATION_MARK(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::EXCLAMATION_MARK, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterTypeNullability($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitTypeNullability($this);
            }
        }
    }

    class TypeListSuffixContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_typeListSuffix;
        }

        public function LIST(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LIST, 0);
        }

        public function ARRAY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ARRAY, 0);
        }

        public function typeNullability(): ?TypeNullabilityContext
        {
            return $this->getTypedRuleContext(TypeNullabilityContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterTypeListSuffix($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitTypeListSuffix($this);
            }
        }
    }

    class StringLiteralContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_stringLiteral;
        }

        public function STRING_LITERAL1(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::STRING_LITERAL1, 0);
        }

        public function STRING_LITERAL2(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::STRING_LITERAL2, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterStringLiteral($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitStringLiteral($this);
            }
        }
    }

    class MapContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_map;
        }

        public function LCURLY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LCURLY, 0);
        }

        public function RCURLY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::RCURLY, 0);
        }

        /**
         * @return array<PropertyKeyNameContext>|PropertyKeyNameContext|null
         */
        public function propertyKeyName(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(PropertyKeyNameContext::class);
            }

            return $this->getTypedRuleContext(PropertyKeyNameContext::class, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function COLON(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::COLON);
            }

            return $this->getToken(CypherPathSubset::COLON, $index);
        }

        /**
         * @return array<ExpressionContext>|ExpressionContext|null
         */
        public function expression(?int $index = null)
        {
            if (null === $index) {
                return $this->getTypedRuleContexts(ExpressionContext::class);
            }

            return $this->getTypedRuleContext(ExpressionContext::class, $index);
        }

        /**
         * @return array<TerminalNode>|TerminalNode|null
         */
        public function COMMA(?int $index = null)
        {
            if (null === $index) {
                return $this->getTokens(CypherPathSubset::COMMA);
            }

            return $this->getToken(CypherPathSubset::COMMA, $index);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterMap($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitMap($this);
            }
        }
    }

    class SymbolicNameStringContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_symbolicNameString;
        }

        public function escapedSymbolicNameString(): ?EscapedSymbolicNameStringContext
        {
            return $this->getTypedRuleContext(EscapedSymbolicNameStringContext::class, 0);
        }

        public function unescapedSymbolicNameString(): ?UnescapedSymbolicNameStringContext
        {
            return $this->getTypedRuleContext(UnescapedSymbolicNameStringContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterSymbolicNameString($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitSymbolicNameString($this);
            }
        }
    }

    class EscapedSymbolicNameStringContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_escapedSymbolicNameString;
        }

        public function ESCAPED_SYMBOLIC_NAME(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ESCAPED_SYMBOLIC_NAME, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterEscapedSymbolicNameString($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitEscapedSymbolicNameString($this);
            }
        }
    }

    class UnescapedSymbolicNameStringContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_unescapedSymbolicNameString;
        }

        public function unescapedLabelSymbolicNameString(): ?UnescapedLabelSymbolicNameStringContext
        {
            return $this->getTypedRuleContext(UnescapedLabelSymbolicNameStringContext::class, 0);
        }

        public function NOT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NOT, 0);
        }

        public function null(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NULL, 0);
        }

        public function TYPED(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TYPED, 0);
        }

        public function NORMALIZED(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NORMALIZED, 0);
        }

        public function NFC(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NFC, 0);
        }

        public function NFD(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NFD, 0);
        }

        public function NFKC(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NFKC, 0);
        }

        public function NFKD(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NFKD, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterUnescapedSymbolicNameString($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitUnescapedSymbolicNameString($this);
            }
        }
    }

    class SymbolicLabelNameStringContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_symbolicLabelNameString;
        }

        public function escapedSymbolicNameString(): ?EscapedSymbolicNameStringContext
        {
            return $this->getTypedRuleContext(EscapedSymbolicNameStringContext::class, 0);
        }

        public function unescapedLabelSymbolicNameString(): ?UnescapedLabelSymbolicNameStringContext
        {
            return $this->getTypedRuleContext(UnescapedLabelSymbolicNameStringContext::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterSymbolicLabelNameString($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitSymbolicLabelNameString($this);
            }
        }
    }

    class UnescapedLabelSymbolicNameStringContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_unescapedLabelSymbolicNameString;
        }

        public function unescapedLabelSymbolicNameString_(): ?UnescapedLabelSymbolicNameString_Context
        {
            return $this->getTypedRuleContext(UnescapedLabelSymbolicNameString_Context::class, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterUnescapedLabelSymbolicNameString($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitUnescapedLabelSymbolicNameString($this);
            }
        }
    }

    class UnescapedLabelSymbolicNameString_Context extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_unescapedLabelSymbolicNameString_;
        }

        public function IDENTIFIER(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::IDENTIFIER, 0);
        }

        public function ACCESS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ACCESS, 0);
        }

        public function ACTIVE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ACTIVE, 0);
        }

        public function ADMIN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ADMIN, 0);
        }

        public function ADMINISTRATOR(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ADMINISTRATOR, 0);
        }

        public function ALIAS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ALIAS, 0);
        }

        public function ALIASES(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ALIASES, 0);
        }

        public function ALL_SHORTEST_PATHS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ALL_SHORTEST_PATHS, 0);
        }

        public function ALL(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ALL, 0);
        }

        public function ALTER(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ALTER, 0);
        }

        public function AND(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::AND, 0);
        }

        public function ANY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ANY, 0);
        }

        public function ARRAY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ARRAY, 0);
        }

        public function AS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::AS, 0);
        }

        public function ASC(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ASC, 0);
        }

        public function ASCENDING(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ASCENDING, 0);
        }

        public function ASSIGN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ASSIGN, 0);
        }

        public function AT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::AT, 0);
        }

        public function AUTH(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::AUTH, 0);
        }

        public function BINDINGS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::BINDINGS, 0);
        }

        public function BOOL(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::BOOL, 0);
        }

        public function BOOLEAN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::BOOLEAN, 0);
        }

        public function BOOSTED(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::BOOSTED, 0);
        }

        public function BOTH(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::BOTH, 0);
        }

        public function BREAK(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::BREAK, 0);
        }

        public function BUILT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::BUILT, 0);
        }

        public function BY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::BY, 0);
        }

        public function CALL(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::CALL, 0);
        }

        public function CASCADE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::CASCADE, 0);
        }

        public function CASE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::CASE, 0);
        }

        public function CHANGE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::CHANGE, 0);
        }

        public function CIDR(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::CIDR, 0);
        }

        public function COLLECT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::COLLECT, 0);
        }

        public function COMMAND(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::COMMAND, 0);
        }

        public function COMMANDS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::COMMANDS, 0);
        }

        public function COMPOSITE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::COMPOSITE, 0);
        }

        public function CONCURRENT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::CONCURRENT, 0);
        }

        public function CONSTRAINT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::CONSTRAINT, 0);
        }

        public function CONSTRAINTS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::CONSTRAINTS, 0);
        }

        public function CONTAINS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::CONTAINS, 0);
        }

        public function CONTINUE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::CONTINUE, 0);
        }

        public function COPY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::COPY, 0);
        }

        public function COUNT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::COUNT, 0);
        }

        public function CREATE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::CREATE, 0);
        }

        public function CSV(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::CSV, 0);
        }

        public function CURRENT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::CURRENT, 0);
        }

        public function DATA(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DATA, 0);
        }

        public function DATABASE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DATABASE, 0);
        }

        public function DATABASES(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DATABASES, 0);
        }

        public function DATE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DATE, 0);
        }

        public function DATETIME(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DATETIME, 0);
        }

        public function DBMS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DBMS, 0);
        }

        public function DEALLOCATE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DEALLOCATE, 0);
        }

        public function DEFAULT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DEFAULT, 0);
        }

        public function DEFINED(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DEFINED, 0);
        }

        public function DELETE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DELETE, 0);
        }

        public function DENY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DENY, 0);
        }

        public function DESC(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DESC, 0);
        }

        public function DESCENDING(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DESCENDING, 0);
        }

        public function DESTROY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DESTROY, 0);
        }

        public function DETACH(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DETACH, 0);
        }

        public function DIFFERENT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DIFFERENT, 0);
        }

        public function DISTINCT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DISTINCT, 0);
        }

        public function DRIVER(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DRIVER, 0);
        }

        public function DROP(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DROP, 0);
        }

        public function DRYRUN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DRYRUN, 0);
        }

        public function DUMP(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DUMP, 0);
        }

        public function DURATION(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::DURATION, 0);
        }

        public function EACH(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::EACH, 0);
        }

        public function EDGE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::EDGE, 0);
        }

        public function ELEMENT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ELEMENT, 0);
        }

        public function ELEMENTS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ELEMENTS, 0);
        }

        public function ELSE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ELSE, 0);
        }

        public function ENABLE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ENABLE, 0);
        }

        public function ENCRYPTED(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ENCRYPTED, 0);
        }

        public function END(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::END, 0);
        }

        public function ENDS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ENDS, 0);
        }

        public function ERROR(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ERROR, 0);
        }

        public function EXECUTABLE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::EXECUTABLE, 0);
        }

        public function EXECUTE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::EXECUTE, 0);
        }

        public function EXIST(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::EXIST, 0);
        }

        public function EXISTENCE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::EXISTENCE, 0);
        }

        public function EXISTS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::EXISTS, 0);
        }

        public function FAIL(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::FAIL, 0);
        }

        public function false(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::FALSE, 0);
        }

        public function FIELDTERMINATOR(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::FIELDTERMINATOR, 0);
        }

        public function FINISH(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::FINISH, 0);
        }

        public function FLOAT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::FLOAT, 0);
        }

        public function FOREACH(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::FOREACH, 0);
        }

        public function FOR(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::FOR, 0);
        }

        public function FROM(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::FROM, 0);
        }

        public function FULLTEXT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::FULLTEXT, 0);
        }

        public function FUNCTION(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::FUNCTION, 0);
        }

        public function FUNCTIONS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::FUNCTIONS, 0);
        }

        public function GRANT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::GRANT, 0);
        }

        public function GRAPH(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::GRAPH, 0);
        }

        public function GRAPHS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::GRAPHS, 0);
        }

        public function GROUP(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::GROUP, 0);
        }

        public function GROUPS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::GROUPS, 0);
        }

        public function HEADERS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::HEADERS, 0);
        }

        public function HOME(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::HOME, 0);
        }

        public function ID(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ID, 0);
        }

        public function IF(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::IF, 0);
        }

        public function IMMUTABLE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::IMMUTABLE, 0);
        }

        public function IMPERSONATE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::IMPERSONATE, 0);
        }

        public function IN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::IN, 0);
        }

        public function INDEX(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::INDEX, 0);
        }

        public function INDEXES(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::INDEXES, 0);
        }

        public function INF(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::INF, 0);
        }

        public function INFINITY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::INFINITY, 0);
        }

        public function INSERT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::INSERT, 0);
        }

        public function INT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::INT, 0);
        }

        public function INTEGER(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::INTEGER, 0);
        }

        public function IS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::IS, 0);
        }

        public function JOIN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::JOIN, 0);
        }

        public function KEY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::KEY, 0);
        }

        public function LABEL(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LABEL, 0);
        }

        public function LABELS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LABELS, 0);
        }

        public function LEADING(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LEADING, 0);
        }

        public function LIMITROWS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LIMITROWS, 0);
        }

        public function LIST(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LIST, 0);
        }

        public function LOAD(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LOAD, 0);
        }

        public function LOCAL(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LOCAL, 0);
        }

        public function LOOKUP(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::LOOKUP, 0);
        }

        public function MATCH(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::MATCH, 0);
        }

        public function MANAGEMENT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::MANAGEMENT, 0);
        }

        public function MAP(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::MAP, 0);
        }

        public function MERGE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::MERGE, 0);
        }

        public function NAME(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NAME, 0);
        }

        public function NAMES(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NAMES, 0);
        }

        public function NAN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NAN, 0);
        }

        public function NEW(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NEW, 0);
        }

        public function NODE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NODE, 0);
        }

        public function NODETACH(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NODETACH, 0);
        }

        public function NODES(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NODES, 0);
        }

        public function NONE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NONE, 0);
        }

        public function NORMALIZE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NORMALIZE, 0);
        }

        public function NOTHING(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NOTHING, 0);
        }

        public function NOWAIT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::NOWAIT, 0);
        }

        public function OF(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::OF, 0);
        }

        public function OFFSET(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::OFFSET, 0);
        }

        public function ON(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ON, 0);
        }

        public function ONLY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ONLY, 0);
        }

        public function OPTIONAL(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::OPTIONAL, 0);
        }

        public function OPTIONS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::OPTIONS, 0);
        }

        public function OPTION(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::OPTION, 0);
        }

        public function OR(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::OR, 0);
        }

        public function ORDER(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ORDER, 0);
        }

        public function PASSWORD(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PASSWORD, 0);
        }

        public function PASSWORDS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PASSWORDS, 0);
        }

        public function PATH(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PATH, 0);
        }

        public function PATHS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PATHS, 0);
        }

        public function PLAINTEXT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PLAINTEXT, 0);
        }

        public function POINT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::POINT, 0);
        }

        public function POPULATED(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::POPULATED, 0);
        }

        public function PRIMARY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PRIMARY, 0);
        }

        public function PRIMARIES(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PRIMARIES, 0);
        }

        public function PRIVILEGE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PRIVILEGE, 0);
        }

        public function PRIVILEGES(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PRIVILEGES, 0);
        }

        public function PROCEDURE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PROCEDURE, 0);
        }

        public function PROCEDURES(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PROCEDURES, 0);
        }

        public function PROPERTIES(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PROPERTIES, 0);
        }

        public function PROPERTY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PROPERTY, 0);
        }

        public function PROVIDER(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PROVIDER, 0);
        }

        public function PROVIDERS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::PROVIDERS, 0);
        }

        public function RANGE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::RANGE, 0);
        }

        public function READ(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::READ, 0);
        }

        public function REALLOCATE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::REALLOCATE, 0);
        }

        public function REDUCE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::REDUCE, 0);
        }

        public function REL(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::REL, 0);
        }

        public function RELATIONSHIP(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::RELATIONSHIP, 0);
        }

        public function RELATIONSHIPS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::RELATIONSHIPS, 0);
        }

        public function REMOVE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::REMOVE, 0);
        }

        public function RENAME(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::RENAME, 0);
        }

        public function REPEATABLE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::REPEATABLE, 0);
        }

        public function REPLACE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::REPLACE, 0);
        }

        public function REPORT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::REPORT, 0);
        }

        public function REQUIRE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::REQUIRE, 0);
        }

        public function REQUIRED(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::REQUIRED, 0);
        }

        public function RESTRICT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::RESTRICT, 0);
        }

        public function RETURN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::RETURN, 0);
        }

        public function REVOKE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::REVOKE, 0);
        }

        public function ROLE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ROLE, 0);
        }

        public function ROLES(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ROLES, 0);
        }

        public function ROW(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ROW, 0);
        }

        public function ROWS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ROWS, 0);
        }

        public function SCAN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SCAN, 0);
        }

        public function SECONDARY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SECONDARY, 0);
        }

        public function SECONDARIES(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SECONDARIES, 0);
        }

        public function SEC(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SEC, 0);
        }

        public function SECOND(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SECOND, 0);
        }

        public function SECONDS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SECONDS, 0);
        }

        public function SEEK(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SEEK, 0);
        }

        public function SERVER(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SERVER, 0);
        }

        public function SERVERS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SERVERS, 0);
        }

        public function SET(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SET, 0);
        }

        public function SETTING(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SETTING, 0);
        }

        public function SETTINGS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SETTINGS, 0);
        }

        public function SHORTEST(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SHORTEST, 0);
        }

        public function SHORTEST_PATH(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SHORTEST_PATH, 0);
        }

        public function SHOW(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SHOW, 0);
        }

        public function SIGNED(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SIGNED, 0);
        }

        public function SINGLE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SINGLE, 0);
        }

        public function SKIPROWS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SKIPROWS, 0);
        }

        public function START(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::START, 0);
        }

        public function STARTS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::STARTS, 0);
        }

        public function STATUS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::STATUS, 0);
        }

        public function STOP(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::STOP, 0);
        }

        public function VARCHAR(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::VARCHAR, 0);
        }

        public function STRING(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::STRING, 0);
        }

        public function SUPPORTED(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SUPPORTED, 0);
        }

        public function SUSPENDED(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::SUSPENDED, 0);
        }

        public function TARGET(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TARGET, 0);
        }

        public function TERMINATE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TERMINATE, 0);
        }

        public function TEXT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TEXT, 0);
        }

        public function THEN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::THEN, 0);
        }

        public function TIME(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TIME, 0);
        }

        public function TIMESTAMP(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TIMESTAMP, 0);
        }

        public function TIMEZONE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TIMEZONE, 0);
        }

        public function TO(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TO, 0);
        }

        public function TOPOLOGY(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TOPOLOGY, 0);
        }

        public function TRAILING(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TRAILING, 0);
        }

        public function TRANSACTION(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TRANSACTION, 0);
        }

        public function TRANSACTIONS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TRANSACTIONS, 0);
        }

        public function TRAVERSE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TRAVERSE, 0);
        }

        public function TRIM(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TRIM, 0);
        }

        public function true(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TRUE, 0);
        }

        public function TYPE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TYPE, 0);
        }

        public function TYPES(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::TYPES, 0);
        }

        public function UNION(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::UNION, 0);
        }

        public function UNIQUE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::UNIQUE, 0);
        }

        public function UNIQUENESS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::UNIQUENESS, 0);
        }

        public function UNWIND(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::UNWIND, 0);
        }

        public function URL(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::URL, 0);
        }

        public function USE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::USE, 0);
        }

        public function USER(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::USER, 0);
        }

        public function USERS(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::USERS, 0);
        }

        public function USING(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::USING, 0);
        }

        public function VALUE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::VALUE, 0);
        }

        public function VECTOR(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::VECTOR, 0);
        }

        public function VERTEX(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::VERTEX, 0);
        }

        public function WAIT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::WAIT, 0);
        }

        public function WHEN(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::WHEN, 0);
        }

        public function WHERE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::WHERE, 0);
        }

        public function WITH(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::WITH, 0);
        }

        public function WITHOUT(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::WITHOUT, 0);
        }

        public function WRITE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::WRITE, 0);
        }

        public function XOR(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::XOR, 0);
        }

        public function YIELD(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::YIELD, 0);
        }

        public function ZONE(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ZONE, 0);
        }

        public function ZONED(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::ZONED, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterUnescapedLabelSymbolicNameString_($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitUnescapedLabelSymbolicNameString_($this);
            }
        }
    }

    class EndOfFileContext extends ParserRuleContext
    {
        public function __construct(?ParserRuleContext $parent, ?int $invokingState = null)
        {
            parent::__construct($parent, $invokingState);
        }

        public function getRuleIndex(): int
        {
            return CypherPathSubset::RULE_endOfFile;
        }

        public function EOF(): ?TerminalNode
        {
            return $this->getToken(CypherPathSubset::EOF, 0);
        }

        public function enterRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->enterEndOfFile($this);
            }
        }

        public function exitRule(ParseTreeListener $listener): void
        {
            if ($listener instanceof CypherPathSubsetListener) {
                $listener->exitEndOfFile($this);
            }
        }
    }
}
