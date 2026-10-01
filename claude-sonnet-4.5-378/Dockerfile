FROM mcr.microsoft.com/dotnet/sdk:8.0 AS build
WORKDIR /src

COPY DevPortal.sln .
COPY DevPortal/DevPortal.csproj DevPortal/
RUN dotnet restore

COPY . .
WORKDIR /src/DevPortal
RUN dotnet build -c Release -o /app/build

FROM build AS publish
RUN dotnet publish -c Release -o /app/publish /p:UseAppHost=false

FROM mcr.microsoft.com/dotnet/aspnet:8.0 AS final
WORKDIR /app
EXPOSE 8000

COPY --from=publish /app/publish .
COPY openapi.yaml .

ENV ASPNETCORE_URLS=http://+:8000
ENV ASPNETCORE_ENVIRONMENT=Production

ENTRYPOINT ["dotnet", "DevPortal.dll"]