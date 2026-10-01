using ReserveIt.Models;

namespace ReserveIt.Data;

public static class DbSeeder
{
    public static void Seed(AppDbContext context)
    {
        if (!context.Users.Any())
        {
            var adminUser = new User
            {
                Email = "admin@reserveit.com",
                Name = "Admin User",
                Role = "Admin",
                PasswordHash = BCrypt.Net.BCrypt.HashPassword("admin123")
            };

            var regularUser = new User
            {
                Email = "user@reserveit.com",
                Name = "Regular User",
                Role = "User",
                PasswordHash = BCrypt.Net.BCrypt.HashPassword("user123")
            };

            context.Users.AddRange(adminUser, regularUser);
            context.SaveChanges();
        }

        if (!context.Tables.Any())
        {
            var tables = new[]
            {
                new Table { TableNumber = "T1", Capacity = 2, Location = "Window" },
                new Table { TableNumber = "T2", Capacity = 4, Location = "Center" },
                new Table { TableNumber = "T3", Capacity = 6, Location = "Patio" },
                new Table { TableNumber = "T4", Capacity = 2, Location = "Bar" },
                new Table { TableNumber = "T5", Capacity = 8, Location = "Private Room" }
            };

            context.Tables.AddRange(tables);
            context.SaveChanges();
        }
    }
}