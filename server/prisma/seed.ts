import "dotenv/config";
import bcrypt from "bcryptjs";
import { PrismaClient } from "@prisma/client";

const prisma = new PrismaClient();

async function main() {
  const name = process.env.SEED_ADMIN_NAME ?? "Admin Wonderland";
  const email = process.env.SEED_ADMIN_EMAIL ?? "admin@wonderlandtravel.id";
  const password = process.env.SEED_ADMIN_PASSWORD ?? "ChangeMe123!";

  const passwordHash = await bcrypt.hash(password, 12);

  const user = await prisma.user.upsert({
    where: { email },
    update: { name, passwordHash, role: "ADMIN" },
    create: { name, email, passwordHash, role: "ADMIN" },
  });

  console.log(`Seeded admin user: ${user.email} (role: ${user.role})`);
}

main()
  .catch((err) => {
    console.error(err);
    process.exitCode = 1;
  })
  .finally(async () => {
    await prisma.$disconnect();
  });
