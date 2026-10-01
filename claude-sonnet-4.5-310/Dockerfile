FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /src

COPY BudgetTracker.sln .
COPY BudgetTracker/BudgetTracker.csproj BudgetTracker/
RUN dotnet restore

COPY . .
WORKDIR /src/BudgetTracker
RUN dotnet publish -c Release -o /app/publish

FROM mcr.microsoft.com/dotnet/aspnet:8.0 AS runtime
WORKDIR /app
COPY --from=build /app/publish .

ENV ASPNETCORE_URLS=http://+:8000
EXPOSE 8000

ENTRYPOINT ["dotnet", "BudgetTracker.dll"]