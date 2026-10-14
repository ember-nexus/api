import { Circle, ExtensionCategory, register } from "@antv/g6";

import { getIcon } from "./iconUtil.ts";

// v4's getTextSize(group, ...) needed a G canvas group, which you don't have at
// this point in v5. Measure with an offscreen 2D context instead (renderer-agnostic).
const _canvas =
  typeof document !== "undefined" ? document.createElement("canvas") : null;
const _ctx = _canvas ? _canvas.getContext("2d") : null;

function measureText(
  text: unknown,
  fontSize: number,
  fontFamily: string,
): [number, number] {
  if (!_ctx) return [String(text).length * fontSize * 0.6, fontSize];
  _ctx.font = `${fontSize}px ${fontFamily}`;
  const m = _ctx.measureText(String(text));
  const a = m.actualBoundingBoxAscent,
    d = m.actualBoundingBoxDescent;
  const h = Number.isFinite(a) && Number.isFinite(d) ? a + d : fontSize;
  return [m.width, h];
}

interface IconNodeGeometryOptions {
  labelText?: string;
  fontSize?: number;
  fontFamily?: string;
  iconSize?: number;
}

interface IconNodeGeometry {
  textSize: [number, number];
  outerCircleRadius: number;
  iconY: number;
  textCenterY: number;
  iconSize: number;
  fontSize: number;
  fontFamily: string;
}

// Your original geometry, extracted so the layout can reuse the radius (see below).
export function iconNodeGeometry({
  labelText = "",
  fontSize = 12,
  fontFamily = "Fira Code",
  iconSize = 24,
}: IconNodeGeometryOptions): IconNodeGeometry {
  const textSize = measureText(labelText, fontSize, fontFamily);
  const scw = measureText(" ", fontSize, fontFamily)[0];

  const textRadius = Math.sqrt(textSize[0] ** 2 + textSize[1] ** 2) / 2 + scw;
  const iconRadius = Math.max(
    Math.sqrt(2 * iconSize ** 2) / 2 + scw,
    iconSize / 2 + scw,
  );
  const distance = iconSize / 2 + scw + textSize[1] / 2;
  const uncovered = Math.max(0, distance + iconRadius - textRadius);
  const outerCircleRadius = textRadius + uncovered / 2;

  const iconYTop = -outerCircleRadius + iconRadius;
  const iconYBottom =
    outerCircleRadius - textRadius - textSize[1] / 2 - scw - iconRadius;
  const iconYMiddle = (iconYBottom - iconYTop) / 2 + iconYTop;
  const iconY = Math.max(iconYTop, iconYMiddle);

  const textCenterY = outerCircleRadius - textRadius + scw / 2;
  return {
    textSize,
    outerCircleRadius,
    iconY,
    textCenterY,
    iconSize,
    fontSize,
    fontFamily,
  };
}

class IconNode extends Circle {
  render(attributes = this.parsedAttributes, container): void {
    const labelText = attributes.labelText ?? "";
    const geo = iconNodeGeometry({
      labelText,
      fontSize: attributes.labelFontSize ?? 12,
      fontFamily: attributes.fontFamily ?? "Fira Code",
      iconSize: attributes.iconSize ?? 24,
    });

    // Let Circle draw the key shape (sized to the text), plus ports/states/halo.
    // size = 2*r; label:false suppresses the built-in label so it doesn't double up.
    // `halo` flows through from attributes, so a halo shape only exists when highlight is on.
    super.render(
      { ...attributes, size: geo.outerCircleRadius * 2, label: false },
      container,
    );

    // The text (was group.addShape('text', ...)).
    this.upsert(
      "caption",
      "text",
      {
        x: 0,
        y: geo.textCenterY,
        text: labelText,
        textAlign: "center",
        textBaseline: "middle",
        fontSize: geo.fontSize,
        fontFamily: geo.fontFamily,
        fill: attributes.labelFill ?? "#fff",
      },
      container,
    );

    // The icon (was group.addShape('image', ...)); note `src`, not `img`.
    this.upsert(
      "glyph",
      "image",
      {
        x: Math.round(-geo.iconSize / 2),
        y: Math.round(geo.iconY - geo.iconSize / 2),
        width: geo.iconSize,
        height: geo.iconSize,
        src: getIcon(
          attributes.iconType ?? "data",
          `${geo.iconSize}px`,
          attributes.labelFill ?? "#fff",
        ),
      },
      container,
    );
  }

  // Pulsating highlight. The halo sub-shape only exists when `halo` is truthy,
  // which Graph.ts sets per-node from `data.highlight`, so its presence is the signal.
  onCreate(): void {
    super.onCreate?.();
    const halo = this.shapeMap?.halo;
    if (halo && this.attributes.highlight) {
      halo.animate([{ lineWidth: 0 }, { lineWidth: 12 }], {
        duration: 1000,
        iterations: Infinity,
        direction: "alternate",
      });
    }
  }
}

register(ExtensionCategory.NODE, "icon-node", IconNode);
