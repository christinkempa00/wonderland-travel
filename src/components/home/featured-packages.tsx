import {
  Accent,
  Badge,
  Button,
  Card,
  CardBody,
  CardMedia,
  SectionHeader,
  StarRating,
} from "@/components/ui";

const PACKAGES = [
  {
    name: "Bali",
    days: "4D3N",
    price: "Rp 4.500.000",
    rating: 4.8,
    image: "wonderland-pkg-bali",
    tag: "Populer",
  },
  {
    name: "Yogyakarta",
    days: "3D2N",
    price: "Rp 2.800.000",
    rating: 4.7,
    image: "wonderland-pkg-yogyakarta",
    tag: "Budget Ramah",
  },
  {
    name: "Labuan Bajo",
    days: "5D4N",
    price: "Rp 6.200.000",
    rating: 4.9,
    image: "wonderland-pkg-labuanbajo",
    tag: "Terlaris",
  },
];

export function FeaturedPackages() {
  return (
    <section className="mx-auto flex w-full max-w-7xl flex-col gap-12 px-6 py-24">
      <div className="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
        <SectionHeader
          align="left"
          badge="Paket Unggulan"
          title={
            <>
              Destinasi <Accent>Favorit</Accent> Bulan Ini
            </>
          }
          description="Paket wisata yang paling banyak dipilih oleh para petualang kami."
        />
        <Button href="/paket-wisata" variant="outline" className="shrink-0">
          Lihat Semua Paket
        </Button>
      </div>

      <div className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
        {PACKAGES.map((pkg) => (
          <Card key={pkg.name} interactive>
            <CardMedia>
              <img src={`https://picsum.photos/seed/${pkg.image}/640/480`} alt={pkg.name} />
            </CardMedia>
            <CardBody className="flex flex-col gap-3">
              <div className="flex items-center justify-between">
                <Badge variant="neutral">{pkg.tag}</Badge>
                <StarRating rating={pkg.rating} />
              </div>
              <h3 className="text-xl font-bold text-heading">{pkg.name}</h3>
              <p className="text-sm text-muted">{pkg.days} · Termasuk akomodasi &amp; pemandu</p>
              <div className="mt-2 flex items-center justify-between">
                <span className="text-lg font-bold text-heading">{pkg.price}</span>
                <Button href="/paket-wisata" size="sm">
                  Lihat Detail
                </Button>
              </div>
            </CardBody>
          </Card>
        ))}
      </div>
    </section>
  );
}
