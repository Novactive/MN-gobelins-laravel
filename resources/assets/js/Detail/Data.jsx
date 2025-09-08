import React from "react";
import nl2br from "react-nl2br";
import { Media } from "react-breakpoints";

function DataUnitTemplate(props) {
  return (
    <div className="DetailData__unit">
      <dt className="DetailData__label">{props.label}</dt>
      <dd className="DetailData__datum">{props.value}</dd>
    </div>
  );
}

function formatInventoryId(inventoryId) {
  if (!inventoryId) return '';
  
  return inventoryId
      .replace(/^([A-Z]+)-(\d+)/, "$1 $2")
      .replace(/-(\d{3})$/, (match, p1) => (p1 === "000" ? "" : `/${p1}`))
      .replace(/-(\d{3})/g, "/$1");
}

function InventoryId(props) {
  return props.inventoryId ? (
    <DataUnitTemplate label="Numéro d’inventaire" value={formatInventoryId(props.inventoryId)} />
  ) : null;
}

function Authors(props) {
  const label_singular = "Auteur";
  const label_plural = "Auteurs";
  if (
    props.authors &&
    props.authors instanceof Array &&
    props.authors.length > 0
  ) {
    const label = props.authors.length > 1 ? label_plural : label_singular;
    return (
      <DataUnitTemplate
        label={label}
        value={props.authors
          .map(a => [a.first_name, a.last_name, a.dates].join(" "))
          .join(", ")}
      />
    );
  } else {
    return null;
  }
}

function ConceptionYear(props) {
  const value = props.conceptionYearAsText || props.conceptionYear;
  return value ? (
    <DataUnitTemplate
      label="Année de conception"
      value={value}
    />
  ) : null;
}

function Style(props) {
  return props.style && props.style.name ? (
    <DataUnitTemplate label="Style" value={props.style.name} />
  ) : null;
}

function Types(props) {
  const label_singular = "Type";
  const label_plural = "Types";
  if (props.types && props.types instanceof Array && props.types.length > 0) {
    const label = props.types.length > 1 ? label_plural : label_singular;
    const val = props.types
      .filter(t => t.is_leaf)[0]
      .mapping_key.split(" > ")
      .filter((v, i, a) => a.indexOf(v) === i)
      .join(", ");
    return <DataUnitTemplate label={label} value={val} />;
  } else {
    return null;
  }
}

function Period(props) {
  if (!props.period || !props.period.name) return null;
  const { name, startYear, endYear } = props.period;
  const hasDateAtEnd = /\(\d{4}\s*-\s*\d{4}\)$/.test(name);
  const dateRange =
      !hasDateAtEnd && startYear && endYear ? ` (${startYear} - ${endYear})` : '';
  return (
      <DataUnitTemplate
          label="Époque"
          value={`${name}${dateRange}`}
      />
  );
}

function Materials(props) {
  const label_singular = "Matière";
  const label_plural = "Matières";
  if (
    props.materials &&
    props.materials instanceof Array &&
    props.materials.length > 0
  ) {
    const label = props.materials.length > 1 ? label_plural : label_singular;
    return (
      <DataUnitTemplate
        label={label}
        value={props.materials
          // Annoying corner-case: don't repeat "cuir" and "skaï".
          // Fortunately, we don't have any products classified in
          // the generic "cuir et skaï" category…
          .filter(m => m.name != "Cuir et skaï")
          .map(m => m.name)
          .join(", ")}
      />
    );
  } else {
    return null;
  }
}

function ProductionOrigin(props) {
  return props.productionOrigin && props.productionOrigin.name ? (
    <DataUnitTemplate
      label="Manufacture et atelier"
      value={props.productionOrigin.name}
    />
  ) : null;
}

/* By default, values are in meters. */
function Dimensions(props) {
  const has_dims = props.dimensions && props.dimensions.trim().length > 0;
  return has_dims ? (
    <DataUnitTemplate
      label={props.label}
      value={
        props.dimensions
      }
    />
  ) : null;
}

function Acquisition(props) {
  const acquisitionDate = props.acquisitionDate
    ? new Date(props.acquisitionDate).toLocaleDateString("fr-FR", {
        day: "numeric",
        month: "short",
        year: "numeric"
      })
    : null;
  return props.acquisitionOrigin ||
    props.acquisitionDate ||
    (props.acquisitionMode && props.acquisitionMode.name) ? (
    <DataUnitTemplate
      label="Acquisition"
      value={[
        acquisitionDate,
        props.acquisitionMode.name,
        props.acquisitionOrigin
      ]
        .filter(Boolean)
        .join(" – ")}
    />
  ) : null;
}

function LegacyInventoryNumber(props) {
  return props.legacyInventoryNumber ? (
    <DataUnitTemplate
      label="Ancien numéro d’inventaire"
      value={nl2br(props.legacyInventoryNumber)}
    />
  ) : null;
}

function Photographer(props) {
  const label =
    props.mainImage && props.mainImage.photographer
      ? "Photographie © " + props.mainImage.photographer
      : "Photographie © Mobilier national, droits réservés";
  return <DataUnitTemplate label={label} value="" />;
}

function Data(props) {
  return (
    <dl className="DetailData">
      <Media>
        {({ breakpoints, currentBreakpoint }) =>
          breakpoints[currentBreakpoint] >= breakpoints.tablet &&
          breakpoints[currentBreakpoint] < breakpoints.small &&
          props.title
        }
      </Media>
      <div className="DetailData__columns">
        <InventoryId inventoryId={props.product.inventory_id} />
        <Authors authors={props.product.authors} />
        <ConceptionYear 
          conceptionYear={props.product.conception_year}
          conceptionYearAsText={props.product.conception_year_as_text}
        />
        <Style style={props.product.style} />
        <Types types={props.product.product_types} />
        <Period
          period={{
            name: props.product.period_name,
            startYear: props.product.period_start_year,
            endYear: props.product.period_end_year
          }}
        />
        <Materials materials={props.product.materials} />
        <ProductionOrigin productionOrigin={props.product.production_origin} />
        <Dimensions
          label={props.product.formatted_dimensions.label}
          dimensions={props.product.formatted_dimensions.dimensions}
        />
        <Acquisition
          acquisitionDate={props.product.acquisition_date}
          acquisitionOrigin={props.product.acquisition_origin}
          acquisitionMode={props.product.acquisition_mode}
        />
        <LegacyInventoryNumber
          legacyInventoryNumber={props.product.legacy_inventory_number}
        />
        <Photographer mainImage={props.mainImage} />
      </div>
    </dl>
  );
}

export default Data;
